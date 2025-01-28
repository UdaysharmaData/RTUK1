<?php

namespace App\Rules;

use App\Http\Helpers\AccountType;
use App\Modules\Partner\Models\Partner;
use App\Modules\Partner\Models\PartnerChannel;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\InvokableRule;

class CanRoleUpdatePartner implements DataAwareRule, InvokableRule
{
    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data = [];
    protected Partner $partner;

    public function __construct(Partner $partner)
    {
        $this->partner = $partner;
    }

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
        $query = PartnerChannel::whereHas('partner', function ($query) {
            $query->where('ref', $this->partner->ref);
        });

        if (! AccountType::isDeveloper() && $query->exists()) {
            $fail("Only developers can update the :attribute of partners having channels.");
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
