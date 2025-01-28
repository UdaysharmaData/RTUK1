<?php

namespace App\Filters;

use App\Enums\ListingFaqsFilterOptionsEnum;

class FaqsFilter extends Filters
{
    /**
     * @return array|string[]
     */
    protected array $filters = [
        'faqs'
    ];

    /**
     * @param string $field
     * @return void
     */
    public function faqs(string $field): void
    {
        $this->builder->when(
            $value = ListingFaqsFilterOptionsEnum::tryFrom($field)?->value,
            function($query) use ($value) {
                if ($value === ListingFaqsFilterOptionsEnum::With->value)
                    $query->has('faqs');
                else if ($value === ListingFaqsFilterOptionsEnum::Without->value)
                    $query->doesntHave('faqs');
            }
        );
    }
}
