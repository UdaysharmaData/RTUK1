<?php

namespace App\Services\Importer\Conditions;

use App\Services\Importer\Contracts\ImportConditionInterface;

class ExampleCondition implements ImportConditionInterface
{
    public function __construct()
    {
    }

    /**
     * @param array $data
     * @return bool
     */
    public function isPassed(array $data): bool
    {
        // do some validation on $data and return true or false validation result
        return true;
    }
}
