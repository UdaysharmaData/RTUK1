<?php

namespace App\Filters;

use App\Enums\MonthEnum;

class MonthFilter extends Filters
{
    /**
     * @return array|string[]
     */
    protected array $filters = [
        'month'
    ];

    /**
     * @var string
     */
    protected string $column = 'created_at';

    /**
     * @param string $value
     * @return MonthFilter
     */
    public function setColumn(string $value): MonthFilter
    {
        $this->column = $value;

        return $this;
    }

    /**
     * @param string $field
     * @return void
     */
    public function month(string $field): void
    {
        $this->builder->when(
            $month = MonthEnum::tryFrom($field)?->value,
            fn($query) => $query->whereMonth($this->column, '=', $month)
        );
    }
}
