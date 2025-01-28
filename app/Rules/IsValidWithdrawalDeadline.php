<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\InvokableRule;

class IsValidWithdrawalDeadline implements DataAwareRule, InvokableRule
{
    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        if ($value && (!isset($this->data['withdrawals']) || (isset($this->data['withdrawals']) && !$this->data['withdrawals']))) { // Ensure withdrawals is set to true whenever a withdrawal_deadline is set
            // $fail('The :attribute should not be set when withdrawals is false.');
            $fail('The withdrawal_deadline should not be set when withdrawals is false.');
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
