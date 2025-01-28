<?php

namespace App\Rules;

use App\Models\Region;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\InvokableRule;

class EnsureRegionBelongsToCountry implements DataAwareRule, InvokableRule
{
    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Ensure the event is active
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        $paramValue = $this->data['country'] ?? null;
        $value = explode(',', $value); // Convert the string into an array
        if ($paramValue) {
            $query = Region::whereIn('ref', $value)
                ->where('country', $paramValue);

            if ($query->doesntExist()) {
                $fail("The region does not belong to the selected country.");
                // TODO: LOG a message to notify the developer's on slack
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
