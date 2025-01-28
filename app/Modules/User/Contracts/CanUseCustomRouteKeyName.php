<?php

namespace App\Modules\User\Contracts;

interface CanUseCustomRouteKeyName
{
    /**
     * @return string
     */
    public function getRouteKeyName(): string;
}
