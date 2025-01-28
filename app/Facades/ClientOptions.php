<?php

namespace App\Facades;

use App\Services\ClientOptions\OptionsConfig;
use Illuminate\Support\Facades\Facade;

class ClientOptions extends Facade {
    /**
     * @see OptionsConfig
     */
    protected static function getFacadeAccessor(): string
    {
        return 'client-options';
    }
}
