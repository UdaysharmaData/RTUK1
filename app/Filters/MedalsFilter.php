<?php

namespace App\Filters;

use App\Enums\ListingMedalsFilterOptionsEnum;

class MedalsFilter extends Filters
{
    /**
     * @return array|string[]
     */
    protected array $filters = [
        'medals'
    ];

    /**
     * @param string $field
     * @return void
     */
    public function medals(string $field): void
    {
        $this->builder->when(
            $value = ListingMedalsFilterOptionsEnum::tryFrom($field)?->value,
            function($query) use ($value) {
                if ($value === ListingMedalsFilterOptionsEnum::With->value)
                    $query->has('medals');
                else if ($value === ListingMedalsFilterOptionsEnum::Without->value)
                    $query->doesntHave('medals');
            }
        );
    }
}
