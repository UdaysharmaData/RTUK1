<?php

namespace App\Traits;

use App\Services\ApiClient\ApiPlatformUserIdentifierValidator;
use App\Services\ApiClient\ApiClientSettings;
use Stevebauman\Location\Facades\Location;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

trait AddRequestMetaAttributes
{
    /**
     * @return void
     * @throws \Exception
     */
    public static function bootAddRequestMetaAttributes(): void
    {
        $agent = new \Jenssegers\Agent\Agent();
        $request = request();
        $position = Location::get() ?? null;

        static::creating(function ($model) use ($request, $agent, $position) {
            $model->ip = $request?->ip();
            $model->device = $agent->device();
            $model->device_type = self::getDeviceType($agent);
            $model->browser = $agent->browser();
            $model->platform = $agent->platform();
            $model->is_bot = $agent->isRobot();
            $model->country = $position->countryName ?? null;

            if (! is_null($identifier = self::getIdentifier())) {
                $model->identifier = $identifier;
            }
        });
    }

    /**
     * @param Agent $agent
     * @return string|null
     */
    private static function getDeviceType(\Jenssegers\Agent\Agent $agent): ?string
    {
        if ($agent->isMobile()) {
            $deviceType = 'mobile';
        } elseif ($agent->isTablet()) {
            $deviceType = 'tablet';
        } elseif ($agent->isDesktop()) {
            $deviceType = 'desktop';
        } else $deviceType = null;

        return $deviceType;
    }

    /**
     * @return string|null
     * @throws \Exception
     */
    private static function getIdentifier(): ?string
    {
        try {
            if (! is_null($token = ApiClientSettings::requestIdentifierToken())) {
                return (new ApiPlatformUserIdentifierValidator(
                    $token, clientSiteCode()
                ))->validated();
            } else {
                throw new \Exception('Identifier value not set for key: ' . ApiClientSettings::getRequestIdentifierKey());
            }
        } catch (\Exception $exception) {
            Log::error($exception);

            return null;
        }
    }
}
