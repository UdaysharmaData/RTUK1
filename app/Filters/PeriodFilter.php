<?php

namespace App\Filters;

use App\Enums\TimeReferenceEnum;
use App\Services\TimePeriodReferenceService;

class PeriodFilter extends Filters
{
    /**
     * @return array|string[]
     */
    protected array $filters = [
        'period'
    ];

    /**
     * @var string
     */
    protected string $column = 'created_at';

    /**
     * @param string $value
     * @return PeriodFilter
     */
    public function setColumn(string $value): PeriodFilter
    {
        $this->column = $value;

        return $this;
    }

    /**
     * @param string $field
     * @return void
     */
    public function period(string $field): void
    {
        $this->builder->when(
            ($period = TimeReferenceEnum::tryFrom($field)?->value)
                && ($field !== TimeReferenceEnum::All->value),
            fn($query) => $query->where($this->column, '>=', (new TimePeriodReferenceService($period))->toCarbonInstance())
        );
    }
}
