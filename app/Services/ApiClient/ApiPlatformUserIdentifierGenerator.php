<?php

namespace App\Services\ApiClient;

use Illuminate\Support\Str;

class ApiPlatformUserIdentifierGenerator
{
    /**
     * Components parts of a valid identifier
     */
    const IDENTIFIER_COMPONENTS = [
        'platform_code',
        'api_version',
        'unique_token',
        'generated_at_timestamp'
    ];

    /**
     * @return string|null
     */
    public static function generate(): ?string
    {
        $identifier = null;
        $components = self::IDENTIFIER_COMPONENTS;
        $componentsCount = count($components);

        foreach ($components as $key => $component) {
            $generator = Str::camel($component);
            $delimiter = ($key === ($componentsCount - 1)) ? null : '.';
            $identifier.= (self::$generator() . $delimiter);
        }

        return $identifier;
    }

    /**
     * @return string
     */
    private static function platformCode(): string
    {
        return Str::upper(clientSiteCode());
    }

    /**
     * @return string
     */
    private static function ApiVersion(): string
    {
        return config('app.api_version');
    }

    /**
     * @return string
     */
    private static function uniqueToken(): string
    {
        return Str::orderedUuid();
    }

    /**
     * @return string
     */
    private static function generatedAtTimestamp(): string
    {
        return now()->timestamp;
    }
}
