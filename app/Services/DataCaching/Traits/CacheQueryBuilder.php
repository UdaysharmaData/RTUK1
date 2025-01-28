<?php

namespace App\Services\DataCaching\Traits;

use App\Services\DataCaching\Builder;

trait CacheQueryBuilder
{
    /**
     * Get a new query builder instance for the connection.
     *
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder(): Builder|\Illuminate\Database\Query\Builder
    {
        $connection = $this->getConnection();

        $grammar = $connection->getQueryGrammar();

        return new Builder($connection, $grammar, $connection->getPostProcessor());
    }
}
