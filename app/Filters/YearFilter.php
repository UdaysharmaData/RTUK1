<?php

namespace App\Filters;

class YearFilter extends Filters
{
    /**
     * @return array|string[]
     */
    protected array $filters = [
        'year'
    ];

    /**
     * @var string
     */
    protected string $column = 'created_at';

    /**
     * @param string $value
     * @return YearFilter
     */
    public function setColumn(string $value): YearFilter
    {
        $this->column = $value;

        return $this;
    }

    /**
     * @param string $field
     * @return void
     */
    public function year(string $field): void
    {
        $this->builder->when($field, fn($query) => $query->whereYear($this->column, '=', $field));
    }
}
