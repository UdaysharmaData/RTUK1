<?php

namespace App\Rules;

use Rule;
use App\Modules\Charity\Models\ResaleRequest;
use App\Modules\Participant\Models\Participant;
use App\Modules\Charity\Models\CharityMembership;
use App\Modules\Charity\Models\EventPlaceInvoice;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\InvokableRule;
use App\Modules\Charity\Models\CharityPartnerPackage;
use App\Modules\Participant\Models\FamilyRegistration;

class validateInvoiceItemItemRefAgainstItemClass implements DataAwareRule, InvokableRule
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
        if ($value) { // Validate the item_ref against the class (invoice_itemable_type) corresponding to the item_class

            if (! isset($this->data['item_class']))
                $fail('The item_class field is required.');

            if (! isset($this->data['item_ref']))
                $fail('The item_ref field is required.');

            if (! in_array($this->data['item_class'], [Participant::class, EventPlaceInvoice::class, CharityMembership::class, ResaleRequest::class, CharityPartnerPackage::class, FamilyRegistration::class])) {
                $fail("The item_class field is invalid");
            }

            if ($this->data['item_class'] == Participant::class)
                $_validate = Participant::where('ref', $this->data['item_ref']);

            if ($this->data['item_class'] == EventPlaceInvoice::class)
                $_validate = EventPlaceInvoice::where('ref', $this->data['item_ref']);

            if ($this->data['item_class'] == CharityMembership::class)
                $_validate = CharityMembership::where('ref', $this->data['item_ref']);

            if ($this->data['item_class'] == ResaleRequest::class)
                $_validate = ResaleRequest::where('ref', $this->data['item_ref']);

            if ($this->data['item_class'] == CharityPartnerPackage::class)
                $_validate = CharityPartnerPackage::where('ref', $this->data['item_ref']);

            if ($this->data['item_class'] == FamilyRegistration::class)
                $_validate = FamilyRegistration::where('ref', $this->data['item_ref']);

            if (isset($_validate) && ! $_validate->exists()) { // Check if the ref exists for the selected item_class (invoice_itemable_type)
                $fail('The selected item_ref does not exists for the selected item_class.');
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
