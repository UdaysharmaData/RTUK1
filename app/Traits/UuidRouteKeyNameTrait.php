<?php

namespace App\Traits;

trait UuidRouteKeyNameTrait
{
    /**
     * get route-model binding attribute.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'ref';
    }
}
