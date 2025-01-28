<?php

namespace App\Services\SocialiteMultiTenancySupport\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SocialiteMultiTenancySupport\Contracts\SocialiteMultiTenancyContract;
use App\Services\SocialiteMultiTenancySupport\Exceptions\InvalidPlatformException;
use App\Services\SocialiteMultiTenancySupport\Exceptions\UnsupportedSocialiteProviderException;
use App\Services\SocialiteMultiTenancySupport\Exceptions\UnsupportedSourceClientUrlException;
use App\Services\SocialiteMultiTenancySupport\Facades\SocialitePlus;
use App\Services\SocialiteMultiTenancySupport\Traits\SocialitePlusTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\Provider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use function config;
use function request;

abstract class SocialiteBaseController extends Controller implements SocialiteMultiTenancyContract
{
    use SocialitePlusTrait;

    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth.socials');
    }

    public abstract function callback();

    /**
     * @return RedirectResponse|\Illuminate\Http\RedirectResponse
     */
    public function redirect(): RedirectResponse|\Illuminate\Http\RedirectResponse
    {
        try {
            $platform = $this->getRequestPlatform(request('key'));
            $supportedSources = config("services.$platform.supported_socials_sources", []);

            if (! in_array($source = request('source'), $supportedSources)) {
                throw new UnsupportedSourceClientUrlException("A valid auth service Source [$source] not specified in request URL.");
            }

            $this->cacheRequestOriginUrl($source);

            return $this->getProvider()->redirect();
        } catch (UnsupportedSourceClientUrlException|InvalidPlatformException|\RuntimeException $exception) {
            abort(403, $exception->getMessage());
        } catch (\Exception $exception) {
            Log::error($exception);
            abort(403, 'An error occurred while you were trying to authenticate. Please try again in a bit');
        }
    }

    /**
     * @return Provider
     * @throws InvalidPlatformException|UnsupportedSocialiteProviderException
     */
    protected function getProvider(): Provider
    {
        return SocialitePlus::driver($this->getSocialsProviderName())->stateless();
    }

    /**
     * @return string
     * @throws InvalidPlatformException
     * @throws UnsupportedSocialiteProviderException
     */
    protected function getSocialsProviderName(): string
    {
        $platform = $this->getRequestPlatform(request('key'));
        $provider = strtolower(request('provider'));

        if (! config()->has("services.$platform.$provider")) {
            throw new UnsupportedSocialiteProviderException(sprintf(
                'Auth service for [%s] is not currently supported.', $provider
            ));
        }

        return $provider;
    }

    /**
     * @return string
     */
    public static function getHashedRequestIdentity(): string
    {
        $request = request();

        if (is_null($ip = $request->ip()) || is_null($userAgent = $request->userAgent())) {
            throw new \RuntimeException('Unable to generate request signature.');
        }

        return sha1($ip . $userAgent);
    }

    /**
     * @return string
     */
    protected function getRequestOriginCacheKey(): string
    {
        return 'social-auth-source-'.static::getHashedRequestIdentity();
    }

    /**
     * @param mixed $source
     * @return void
     */
    private function cacheRequestOriginUrl(mixed $source): void
    {
        if (Cache::has($key = $this->getRequestOriginCacheKey())) {
            Cache::forget($key);
        }

        Cache::remember($key, now()->addMinutes(5), function() use ($source) {
            return "https://$source";
        });
    }
}
