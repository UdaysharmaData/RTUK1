<?php

namespace App\Http\Controllers;

use App\Modules\User\Models\User;
use App\Services\SocialiteMultiTenancySupport\Controllers\SocialiteBaseController;
use Illuminate\Support\Facades\Log;

class SocialsConnectController extends SocialiteBaseController
{
    /**
     * @throws \App\Services\SocialiteMultiTenancySupport\Exceptions\UnsupportedSocialiteProviderException
     * @throws \App\Services\SocialiteMultiTenancySupport\Exceptions\InvalidPlatformException
     */
    public function callback(): \Illuminate\Routing\Redirector|\Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse
    {
        try {
            $providerName = $this->getSocialsProviderName();
            $providerUser = $this->getProvider()->user();
            $nickname = $providerUser->getNickname();
            $email = $providerUser->getEmail();
            $profileUrl = $this->getDriverBaseProfileUrl().$nickname;
            $match = User::where('email', $email)->firstOrFail();

            $match?->socials()->updateOrCreate(
                ['platform' => $providerName],
                ['url' => $profileUrl]
            );
        } catch (\Exception $exception) {
            Log::error($exception);
        }

        return redirect('/');
    }

    /**
     * @throws \App\Services\SocialiteMultiTenancySupport\Exceptions\UnsupportedSocialiteProviderException
     * @throws \App\Services\SocialiteMultiTenancySupport\Exceptions\InvalidPlatformException
     * @throws \Exception
     */
    public function getDriverBaseProfileUrl(): string
    {
        $providerName = $this->getSocialsProviderName();

        return match ($providerName) {
            'github', => 'https://github.com/',
            'twitter', => 'https://twitter.com/',
            'instagram', => 'https://instagram.com/',
            'facebook' => 'https://facebook.com/',
            'google', => 'https://google.com/',
            'linkedin', => 'https://linkedin/',
            default => throw new \Exception("Unknown provider [$providerName]."),
        };
    }
}
