<?php

namespace App\Rules;

use App\Modules\User\Models\User;
use App\Modules\Participant\Models\Participant;
use App\Modules\Event\Models\EventEventCategory;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\InvokableRule;

class IsRegisteredToEEC implements DataAwareRule, InvokableRule
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
        if (! isset($value)) {
            $fail('The email field is required.');
            return;
        }

        if (! isset($this->data['eec'])) {
            $fail('The event and category fields are required.');
            return;
        }

        $user = User::where('email', $value)
            ->withTrashed()
            ->first();
        
        if (!$user) {
            return;
        }

        if ($user->deleted_at) {
            $fail('The user was soft deleted.');
            return;
        }

        if (! is_array($this->data['eec'])) {
            if (!$eec = EventEventCategory::where('ref', $this->data['eec'])->first()) { // Check if the eec exists
                $fail('The event and category were not found.');
                return;
            }

            $result = Participant::isRegisteredToEEC($user, $eec);

            if ($result->status) {
                $fail($result->message);
                return;
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
