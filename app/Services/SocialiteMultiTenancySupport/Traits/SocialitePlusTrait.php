<?php

namespace App\Services\SocialiteMultiTenancySupport\Traits;

use App\Modules\Setting\Models\Site;
use App\Services\SocialiteMultiTenancySupport\Exceptions\InvalidPlatformException;
use Illuminate\Support\Facades\Cache;

trait SocialitePlusTrait
{
    /**
     * @param string|null $platformKey
     * @return string
     * @throws InvalidPlatformException
     */
    protected function getRequestPlatform(string $platformKey = null): string
    {
        $platformQuery = Site::where('key', clientSite()?->key ?? $platformKey);

        if ($platformQuery->doesntExist()) {
            throw new InvalidPlatformException(sprintf(
                'Unable to identify platform matching [%s].', $platformKey
            ));
        }

        return $platformQuery->first()?->{$this->getPlatformServiceAttribute()};
    }

    /**
     * @return string
     */
    protected function getPlatformServiceAttribute(): string
    {
        return 'code';
    }
}
