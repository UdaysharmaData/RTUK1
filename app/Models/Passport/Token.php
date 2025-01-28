<?php

namespace App\Models\Passport;

use App\Services\DataCaching\Traits\CacheQueryBuilder;
use Laravel\Passport\Token as BaseToken;

class Token extends BaseToken
{
    use CacheQueryBuilder;
}
