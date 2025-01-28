<?php

namespace App\Http\Controllers\Auth;

use App\Enums\SocialPlatformEnum;
use App\Enums\UploadUseAsEnum;
use App\Modules\User\Models\User;
use App\Services\FileManager\FileManager;
use App\Services\FileManager\Traits\SingleUploadModel;
use App\Services\SignedExternalUrlProcessor;
use App\Services\SocialiteMultiTenancySupport\Controllers\SocialiteBaseController;
use App\Services\SocialiteMultiTenancySupport\Exceptions\InvalidPlatformException;
use App\Services\SocialiteMultiTenancySupport\Exceptions\UnsetPlatformRedirectUrlException;
use App\Services\SocialiteMultiTenancySupport\Exceptions\UnsupportedSocialiteProviderException;
use App\Traits\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SocialAuthController extends SocialiteBaseController
{
    use Response, SingleUploadModel;

    /**
     * @var string[]
     */
    const AUTH_TYPES = [
        'new' => 'register',
        'returning' => 'login'
    ];

    /**
     * @var bool
     */
    protected bool $recentlyCreated = false;

    /**
     * @var string
     */
    protected string $authType;

    /**
     * @var string
     */
    protected string $errorMessage = "An error occurred while you were trying to authenticate. Please try again in a bit";

    /**
     * @var string
     */
    protected string $cachedSource;

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    public function callback(): \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
    {
        $this->cachedSource = $this->getCachedSocialsRedirectUrl();
        $this->setSocialAuthType();

        try {
            $providerUser = $this->getProvider()->user();
            $data = $this->getUserDataFromProvider($providerUser);
            $redirectTo = $this->cachedSource;

            if (isset($data['email'])) {
                $user = $this->updateOrCreateUserFromSocialiteData($data);

                if (! is_null($user)) {
//                    $this->updateUserNickname($data['nickname'], $user);
                    $url = $this->getSignedOriginUrl();
                    $this->cacheValidatedUserInfo($url, $user);

                    return redirect($url);
                }
            } else {
                $provider = strtolower(request('provider'));
                $this->errorMessage = "We couldn't find an email associated with your $provider account. Please try another social provider or use the form below.";
            }

            $errorKey = Str::random();
            Cache::put($errorKey, $this->errorMessage, now()->addMinutes(5));
            $redirectTo.="?error=$errorKey";

            return redirect($redirectTo);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            throw new \Exception("An error occurred while you were trying to $this->authType. Please try again in a bit.", 400);
        }
    }

    /**
     * @param \Laravel\Socialite\Contracts\User $providerUser
     * @return array
     */
    private function getUserDataFromProvider(\Laravel\Socialite\Contracts\User $providerUser): array
    {
        $names = explode(' ', $providerUser->getName());

        return [
            'email' => $providerUser->getEmail(),
            'first_name' => $names[0],
            'last_name' => $names[1],
            'nickname' => $providerUser->getNickname(),
            'avatar' => $providerUser->getAvatar()
        ];
    }

    /**
     * @param array $data
     * @return User|null
     */
    private function createUser(array $data): ?User
    {
        try {
            $query = User::withTrashed()
                ->where('email', $email = $data['email']);

            if ($query->doesntExist()) {
                return DB::transaction(function () use ($data) {
                    $user = User::create(array_merge($data, ['email_verified_at' => now()]));
                    $this->recentlyCreated = true;
                    $user->bootstrapUserRelatedProperties();
                    if ($avatar = $data['avatar']) {
                        $this->createAvatar($avatar, $user);
                    }

                    return $user;
                });
            } else {
                $this->errorMessage = "A user with the email ($email) already exists! Please try to login instead.";
                return null;
            }
        } catch (InvalidPlatformException|UnsupportedSocialiteProviderException|\Exception $e) {
            Log::error($e);
        }
        return null;
    }

    /**
     * @param array $data
     * @return Model|\Illuminate\Database\Eloquent\Builder|null
     */
    private function updateUser(array $data): Model|\Illuminate\Database\Eloquent\Builder|null
    {
        try {
            $query = User::query()
                ->where('email', $email = $data['email']);

            if ($query->exists()) {
                $user = $query->first();
                unset($data['email']);
                $user->update($data);
                $this->recentlyCreated = false;
            } else {
                $this->errorMessage = "A user with the email ($email) doesn't exist! Please try to register first.";
                return null;
            }

            return $user;
        } catch (\Exception $e) {
            Log::error($e);
        }
        return null;
    }

    /**
     * @param User $user
     * @param string $nickname
     * @return Model|null
     * @throws InvalidPlatformException
     * @throws UnsupportedSocialiteProviderException
     */
    private function updateSocials(User $user, string $nickname): ?Model
    {
        $socials = $this->getSocialBaseUrl();
        $provider  = $socials['social'];
        $url = $socials['base_url'];

        if (! is_null($url)) {
            $profileUrl = "$url/$nickname";
            return $user->socials()->updateOrCreate(
                ['platform' => $provider],
                ['url' => $profileUrl, 'is_social_auth' => true]
            );
        }
        return null;
    }

    /**
     * @return array
     * @throws InvalidPlatformException
     * @throws UnsupportedSocialiteProviderException
     */
    public function getSocialBaseUrl(): array
    {
        $url = match ($social = $this->getSocialsProviderName()) {
            SocialPlatformEnum::Facebook->value => 'https://facebook.com',
            SocialPlatformEnum::LinkedIn->value => 'https://linkedin.com',
            SocialPlatformEnum::GitHub->value => 'https://github.com',
            SocialPlatformEnum::Twitter->value => 'https://twitter.com',
            SocialPlatformEnum::Instagram->value => 'https://instagram.com',
            default => null,
        };

        return [
            'social' => $social,
            'base_url' => $url
        ];
    }

    /**
     * @return string
     */
    private function getSocialsEventType(): string
    {
        if ($this->recentlyCreated) {
            $type = self::AUTH_TYPES['new'];
        } else {
            $type = self::AUTH_TYPES['returning'];
        }
        return $type;
    }

    /**
     * @param $avatar
     * @param $user
     * @return void
     * @throws \App\Services\FileManager\Exceptions\UnableToOpenFileFromUrlException
     * @throws \Exception
     */
    private function createAvatar($avatar, $user): void
    {
        $url = Storage::disk(config('filesystems.default'))->putFile(
            self::getPath('image'),
            $file = FileManager::createFileFromUrl($avatar),
            'public'
        );

        $user->profile->upload()->create([
            'url' => $url,
            'type' => FileManager::guessFileType($file),
            'use_as' => UploadUseAsEnum::Avatar->value
        ]);
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getCachedSocialsRedirectUrl(): string
    {
        $cachedSource = Cache::pull($this->getRequestOriginCacheKey());

        if (is_null($cachedSource)) {
            throw new UnsetPlatformRedirectUrlException('Request platform may not have a set redirect URL');
        }

        return $cachedSource;
    }

    /**
     * @param string $url
     * @return string
     */
    private function getSignedUrlDataCacheKey(string $url): string
    {
        $queryParams = SignedExternalUrlProcessor::getQueryParameters($url);

//        return sha1($queryParams['signature']);
        return sha1($queryParams['signature'].static::getHashedRequestIdentity());
    }

    /**
     * @param string $url
     * @param Model|\Illuminate\Database\Eloquent\Builder|User $user
     * @return void
     */
    private function cacheValidatedUserInfo(string $url, Model|\Illuminate\Database\Eloquent\Builder|User $user): void
    {
        Cache::put($this->getSignedUrlDataCacheKey($url), [
            'user_id' => $user->id,
            'event_type' => $this->getSocialsEventType(),
            'origin_url' => $this->cachedSource
        ], now()->addMinutes(5));
    }

    /**
     * @param array $data
     * @return User|\Illuminate\Database\Eloquent\Builder|Model|null
     */
    private function updateOrCreateUserFromSocialiteData(array $data): null|User|\Illuminate\Database\Eloquent\Builder|Model
    {
        if ($this->authType === self::AUTH_TYPES['new']) {
            $user = $this->createUser($data);
        } elseif ($this->authType === self::AUTH_TYPES['returning']) {
            $user = $this->updateUser($data);
        } else $user = null;

        return $user;
    }

    /**
     * @param $nickname1
     * @param Model|\Illuminate\Database\Eloquent\Builder|User $user
     * @return void
     * @throws InvalidPlatformException
     * @throws UnsupportedSocialiteProviderException
     */
    private function updateUserNickname($nickname1, Model|\Illuminate\Database\Eloquent\Builder|User $user): void
    {
        if (!is_null($nickname = $nickname1)) {
            $social = $this->updateSocials($user, $nickname);
        }
    }

    /**
     * @return void
     */
    private function setSocialAuthType(): void
    {
        if (Str::endsWith($this->cachedSource, '/register')) {
            $this->authType = self::AUTH_TYPES['new'];
        } elseif (Str::endsWith($this->cachedSource, '/login')) {
            $this->authType = self::AUTH_TYPES['returning'];
        } else abort(403, 'Invalid auth request origin.');
    }

    /**
     * @return string
     */
    private function getSignedOriginUrl(): string
    {
        return (new SignedExternalUrlProcessor)->signUrl(
            $this->cachedSource,
            now()->addMinutes(5)
        );
    }
}
