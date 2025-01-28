<?php

namespace App\Services\SocialiteMultiTenancySupport\Facades;

use Illuminate\Support\Facades\Facade;

class SocialitePlus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'socialite-multi-tenancy';
    }
}
