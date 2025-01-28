<?php

namespace App\Filters;

use App\Enums\ListDraftedItemsOptionsEnum;

class DraftedFilter extends Filters
{
    /**
     * @return array|string[]
     */
    protected array $filters = [
        'drafted'
    ];

    /**
     * @param string $field
     * @return void
     */
    public function drafted(string $field): void
    {
        match (ListDraftedItemsOptionsEnum::tryFrom($field)?->value) {
            ListDraftedItemsOptionsEnum::With->value => $this->builder->withDrafted(),
            ListDraftedItemsOptionsEnum::Only->value => $this->builder->onlyDrafted(),
            default => $this->builder,
        };
    }
}