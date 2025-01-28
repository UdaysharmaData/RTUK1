<?php

namespace App\Rules;

use App\Enums\FeeTypeEnum;
use App\Modules\Event\Models\EventEventCategory;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\InvokableRule;

class IsEventEventCategoryFeeNotNull implements DataAwareRule, InvokableRule
{
    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Ensure the event event category fee is not null
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        if (! isset($this->data['eec'])) {
            $fail('The event and category fields are required.');
            return;
        }

        $eec = EventEventCategory::where('ref', $this->data['eec']);

        if ($eec->exists()) {
            if ($value == FeeTypeEnum::Local->value) {
                $feeIsNotNull = $eec->whereNotNull('local_fee');
            }

            if ($value == FeeTypeEnum::International->value) {
                $feeIsNotNull = $eec->whereNotNull('international_fee');
            }

            if (isset($feeIsNotNull) && $feeIsNotNull->doesntExist()) {
                $fail("An invoice cannot be created for an event whose {$value} fee is null.");
            }
        }
    }

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
 
        return $this;
    }
}
