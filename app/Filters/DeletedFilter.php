<?php

namespace App\Filters;

use App\Enums\ListSoftDeletedItemsOptionsEnum;

class DeletedFilter extends Filters
{
    /**
     * @return array|string[]
     */
    protected array $filters = [
        'deleted'
    ];

    /**
     * @param string $field
     * @return void
     */
    public function deleted(string $field): void
    {
        match (ListSoftDeletedItemsOptionsEnum::tryFrom($field)?->value) {
            ListSoftDeletedItemsOptionsEnum::With->value => $this->builder->withTrashed(),
            ListSoftDeletedItemsOptionsEnum::Only->value => $this->builder->onlyTrashed(),
            default => $this->builder,
        };
    }
}
