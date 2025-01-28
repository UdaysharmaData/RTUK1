<?php

namespace App\Rules;

use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventEventCategory;
use Illuminate\Contracts\Validation\InvokableRule;

class IsActiveEvent implements InvokableRule
{
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
        if (! isset($value)) {
            $fail('The event and category fields are required.');
            return;
        }

        $eec = EventEventCategory::where('ref', $value)
            ->whereHas('eventCategory', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->makingRequest();
                });
            });

        if ($eec->doesntExist()) { // Check if the event and event category exists for the selected site
            $fail('The event was not found.');
            return;
        }

        $eec = $eec->first();

        $regActive = $eec->registrationActive(request());

        if (! $regActive->status) { // Check if the registration is still active
            $fail($regActive->message);
            return;
        }

        $hasAvailablePlaces = $eec->_hasAvailablePlaces(null, $charity ?? null);

        if (! $hasAvailablePlaces->status) {
            $fail($hasAvailablePlaces->message);
            return;
        }

        $result = $eec->isFeeValidForCheckout();

        if (! $result->status) {
            $fail($result->message);
            return;
        }
    }
}
