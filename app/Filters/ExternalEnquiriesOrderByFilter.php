<?php

namespace App\Filters;

use Illuminate\Support\Str;
use App\Enums\OrderByDirectionEnum;
use App\Enums\ExternalEnquiriesListOrderByFieldsEnum;
use App\Modules\Partner\Models\PartnerChannel;
use App\Modules\User\Models\User;

class ExternalEnquiriesOrderByFilter extends Filters
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
            $property = ExternalEnquiriesListOrderByFieldsEnum::tryFrom(Str::before($param,':'))?->value;
            $direction = OrderByDirectionEnum::tryFrom(Str::after($param,':'))?->value;

            if ($property && $direction) {
                if ($property === ExternalEnquiriesListOrderByFieldsEnum::FullName->value) {
                    $firstName = ExternalEnquiriesListOrderByFieldsEnum::FirstName->value;
                    $lastName = ExternalEnquiriesListOrderByFieldsEnum::LastName->value;

                    $this->builder->orderByRaw("concat($firstName,' ',$lastName) $direction");
                } else if ($property === ExternalEnquiriesListOrderByFieldsEnum::PartnerChannel->value) {
                    $this->builder->orderBy(
                        PartnerChannel::select('name')
                            ->whereColumn('id', 'external_enquiries.partner_channel_id')
                            ->orderBy('name', $direction)
                            ->limit(1)
                    , $direction);
                } else $this->builder->orderBy($property, $direction);
            };
        }
    }
}
