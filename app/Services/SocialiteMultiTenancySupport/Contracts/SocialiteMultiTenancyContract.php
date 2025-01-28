<?php

namespace App\Services\SocialiteMultiTenancySupport\Contracts;

use Illuminate\Http\Request;

interface SocialiteMultiTenancyContract
{
    public function redirect();

    public function callback();
}
