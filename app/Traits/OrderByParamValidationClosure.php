<?php

namespace App\Traits;

use Illuminate\Support\Str;

use App\Enums\ListTypeEnum;
use App\Enums\OrderByDirectionEnum;

trait OrderByParamValidationClosure
{
    /**
     * @param ListTypeEnum $listType
     * @return \Closure
     */
    public function isValidOrderByParameter(ListTypeEnum $listType): \Closure
    {
        return function ($attribute, $value, $fail) use ($listType) {
            $enum = 'App\Enums\\' . Str::ucfirst($listType->value) . "ListOrderByFieldsEnum";
            $property = $enum::tryFrom(Str::before($value, ':'))?->value;
            $direction = OrderByDirectionEnum::tryFrom(Str::after($value, ':'))?->value;

            if (is_null($property)) $fail("Value provided for $attribute query parameter property [$value] is invalid.");
            if (is_null($direction)) $fail("Value provided for $attribute query parameter direction [$value] is invalid.");
        };
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function _prepareForValidation(): void
    {
        if (isset($this->order_by)) {
            $this->merge([
                'order_by' => explode(',', $this->order_by)
            ]);
        }
    }

    /**
     * @return void
     */
    protected function _passedValidation(): void
    {
        if (isset($this->order_by)) {
            $this->replace(['order_by' => implode(',', $this->order_by)]);
        }
    }
}
