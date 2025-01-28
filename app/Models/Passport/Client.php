<?php

namespace App\Models\Passport;

use App\Services\DataCaching\Traits\CacheQueryBuilder;
use Laravel\Passport\Client as BaseClient;

class Client extends BaseClient
{
    use CacheQueryBuilder;
    /**
     * Determine if the client should skip the authorization prompt.
     *
     * @return bool
     */
    public function skipsAuthorization(): bool
    {
        return $this->firstParty();// todo: may need to scrap
    }
}
