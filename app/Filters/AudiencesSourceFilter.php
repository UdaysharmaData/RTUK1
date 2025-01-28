<?php

namespace App\Filters;

use App\Enums\AudienceSourceEnum;

class AudiencesSourceFilter extends Filters
{
    /**
     * @return array|string[]
     */
    protected array $filters = [
        'source'
    ];

    /**
     * @param string $field
     * @return void
     */
    public function source(string $field): void
    {
        $this->builder->when(
            $source = AudienceSourceEnum::tryFrom($field)?->value,
            fn($query) => $query->where('source', '=', $source)
        );
    }
}
