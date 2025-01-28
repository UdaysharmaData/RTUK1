<?php

namespace App\Filters;

use App\Enums\OrderByDirectionEnum;
use App\Enums\UsersListOrderByFieldsEnum;

use Illuminate\Support\Str;

class UserOrderByFilter extends Filters
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
            $property = UsersListOrderByFieldsEnum::tryFrom(Str::before($param,':'))?->value;
            $direction = OrderByDirectionEnum::tryFrom(Str::after($param,':'))?->value;

            if ($property && $direction) {
                if ($property === UsersListOrderByFieldsEnum::FullName->value) {
                    $firstName = UsersListOrderByFieldsEnum::FirstName->value;
                    $lastName = UsersListOrderByFieldsEnum::LastName->value;

                    $this->builder->orderByRaw("concat($firstName,' ',$lastName) $direction");
                } else $this->builder->orderBy($property, $direction);
            }
        }
    }
}
