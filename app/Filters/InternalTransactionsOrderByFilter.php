<?php

namespace App\Filters;

use Illuminate\Support\Str;
use App\Enums\OrderByDirectionEnum;
use App\Modules\Finance\Models\Account;
use App\Enums\InternalTransactionsListOrderByFieldsEnum;

class InternalTransactionsOrderByFilter extends Filters
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
            $property = InternalTransactionsListOrderByFieldsEnum::tryFrom(Str::before($param,':'))?->value;
            $direction = OrderByDirectionEnum::tryFrom(Str::after($param,':'))?->value;

            if ($property && $direction) {
                if ($property === InternalTransactionsListOrderByFieldsEnum::ValidFrom->value || $property === InternalTransactionsListOrderByFieldsEnum::ValidTo->value) {
                    $this->builder->orderBy(
                        Account::select('valid_from')
                            ->whereColumn('id', 'internal_transactions.account_id')
                            ->orderBy($property, $direction)
                            ->limit(1)
                    , $direction);
                } else $this->builder->orderBy($property, $direction);
            };
        }
    }
}
