<?php

namespace App\Services\Auth\Facades;

use Illuminate\Support\Facades\Password;

class ApiPassword extends Password
{
    protected static function getFacadeAccessor(): string
    {
        return 'auth.password';
    }
}
