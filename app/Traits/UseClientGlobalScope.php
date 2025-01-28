<?php

namespace App\Traits;

use App\Scopes\ClientScope;

trait UseClientGlobalScope
{
    /**
     * @return void
     */
    public static function bootUseClientGlobalScope(): void
    {
        static::addGlobalScope(new ClientScope);
    }
}
