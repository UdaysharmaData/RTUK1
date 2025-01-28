<?php

namespace Database\Traits;

use App\Enums\CharityMembershipTypeEnum;

trait EmptySpaceToDefaultData
{
    /**
     * Return the value, default value (if passed) or null.
     * @param mixed $value
     * @param mixed $default
     * @return mixed
     */
    protected function valueOrDefault(mixed $value, mixed $default = null): mixed
    {
        if ($value == '' || $value == ' ' || !$value) {
            return $default;
        }

        return $value;
    }
}