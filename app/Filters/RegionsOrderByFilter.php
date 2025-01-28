<?php

namespace App\Filters;

use Illuminate\Support\Str;
use App\Enums\OrderByDirectionEnum;
use App\Enums\RegionsListOrderByFieldsEnum;

class RegionsOrderByFilter extends Filters
{
    /**
     * @return array|string[]
     */
    protected array $filters = [
        'order_by'
    ];

    /**
     * @param string $fields
     * @return void
     */
    public function orderBy(string $fields): void
    {
        $params = explode(',', $fields);

        foreach ($params as $param) {
            $property = RegionsListOrderByFieldsEnum::tryFrom(Str::before($param,':'))?->value;
            $direction = OrderByDirectionEnum::tryFrom(Str::after($param,':'))?->value;

            if ($property && $direction) {
                $this->builder->orderBy($property, $direction);
            };
        }
    }
}