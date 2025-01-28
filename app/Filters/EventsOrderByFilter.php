<?php

namespace App\Filters;

use App\Enums\OrderByDirectionEnum;
use App\Enums\EventsListOrderByFieldsEnum;
use App\Modules\Event\Models\EventEventCategory;

use Illuminate\Support\Str;

class EventsOrderByFilter extends Filters
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
            $property = EventsListOrderByFieldsEnum::tryFrom(Str::before($param,':'))?->value;
            $direction = OrderByDirectionEnum::tryFrom(Str::after($param,':'))?->value;

            if ($property && $direction) {
                if ($property === EventsListOrderByFieldsEnum::StartDate->value || $property === EventsListOrderByFieldsEnum::EndDate->value) {
                    $this->builder->orderBy(
                        EventEventCategory::select('start_date')
                            ->whereColumn('event_id', 'events.id')
                            ->orderBy($property, $direction)
                            ->limit(1)
                    , $direction);
                } else $this->builder->orderBy($property, $direction);
            };
        }
    }
}
