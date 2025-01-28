<?php

namespace App\Filters;

use Illuminate\Support\Str;
use App\Modules\User\Models\User;
use App\Enums\OrderByDirectionEnum;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Enums\ParticipantsListOrderByFieldsEnum;

class ParticipantsOrderByFilter extends Filters
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
            $property = ParticipantsListOrderByFieldsEnum::tryFrom(Str::before($param,':'))?->value;
            $direction = OrderByDirectionEnum::tryFrom(Str::after($param,':'))?->value;

            if ($property && $direction) {
                if ($property === ParticipantsListOrderByFieldsEnum::FullName->value || $property === ParticipantsListOrderByFieldsEnum::FirstName->value || $property === ParticipantsListOrderByFieldsEnum::LastName->value) {
                    if ($property === ParticipantsListOrderByFieldsEnum::FullName->value) {
                        $firstName = ParticipantsListOrderByFieldsEnum::FirstName->value;
                        $lastName = ParticipantsListOrderByFieldsEnum::LastName->value;

                        $this->builder->orderBy(User::select('first_name')->whereColumn('id', 'participants.user_id')->orderByRaw("concat($firstName,' ',$lastName) $direction")->limit(1), $direction);
                    } else if ($property === ParticipantsListOrderByFieldsEnum::FirstName->value) {
                        $this->builder->orderBy(User::select('first_name')->whereColumn('id', 'participants.user_id')->orderBy('first_name', $direction)->limit(1), $direction);
                    } else if ($property === ParticipantsListOrderByFieldsEnum::LastName->value) {
                        $this->builder->orderBy(User::select('last_name')->whereColumn('id', 'participants.user_id')->orderBy('last_name', $direction)->limit(1), $direction);
                    }
                } else if ($property === ParticipantsListOrderByFieldsEnum::Charity->value) {
                    $this->builder->orderBy(Charity::select('name')->whereColumn('id', 'participants.charity_id')->orderBy('name', $direction)->limit(1), $direction);
                } else if ($property === ParticipantsListOrderByFieldsEnum::Event->value) {
                    $this->builder->orderBy(Event::select('name')->whereHas('eventCategories', function($query) {
                        $query->whereColumn('event_event_category.id', 'participants.event_event_category_id');
                    })->orderBy('name', $direction)->limit(1), $direction);
                }  else $this->builder->orderBy($property, $direction);
            };
        }
    }
}