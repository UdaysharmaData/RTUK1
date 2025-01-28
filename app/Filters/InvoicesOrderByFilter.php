<?php

namespace App\Filters;

use App\Models\InvoiceItem;
use App\Enums\OrderByDirectionEnum;
use App\Enums\InvoicesListOrderByFieldsEnum;

use Illuminate\Support\Str;

class InvoicesOrderByFilter extends Filters
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
            $property = InvoicesListOrderByFieldsEnum::tryFrom(Str::before($param,':'))?->value;
            $direction = OrderByDirectionEnum::tryFrom(Str::after($param,':'))?->value;

            if ($property && $direction) {
                if ($property === InvoicesListOrderByFieldsEnum::Type->value) {
                    $this->builder->orderBy(
                        InvoiceItem::select('type')
                            ->whereColumn('invoice_id', 'invoices.id')
                            ->orderBy($property, $direction)
                            ->limit(1)
                    , $direction);
                } else $this->builder->orderBy($property, $direction);
            };
        }
    }
}
