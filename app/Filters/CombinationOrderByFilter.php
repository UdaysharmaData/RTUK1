<?php

namespace App\Filters;

use App\Enums\CombinationsListOrderByFieldsEnum;
use App\Enums\OrderByDirectionEnum;
use Illuminate\Support\Str;

class CombinationOrderByFilter extends Filters
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
            $property = CombinationsListOrderByFieldsEnum::tryFrom(Str::before($param,':'))?->value;
            $direction = OrderByDirectionEnum::tryFrom(Str::after($param,':'))?->value;

            if ($property && $direction) {
                $this->builder->orderBy($property, $direction);
            }
        }
    }
}
