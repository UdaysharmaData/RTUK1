<?php

namespace App\Rules;

use App\Modules\User\Models\User;
use App\Modules\Participant\Models\Participant;
use App\Modules\Event\Models\EventEventCategory;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
class IsRegisteredToEEC_2 implements DataAwareRule, InvokableRule, ValidatorAwareRule
{
    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data = [];

    /**
     * The validator instance.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

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
        if ((! (isset($this->data['user']) && isset($this->data['user']['email']))) && \Illuminate\Support\Facades\Route::getFacadeRoot()->current()->uri() == "api/v1/payment/checkout/{type}/proceed") { // Don't run this validation for this route
            return;
        }

        if (! (isset($this->data['user']) && isset($this->data['user']['email']))) {
            $fail('The email field is required.');
            return;
        }

        $user = User::where('email', $this->data['user']['email'])
            ->withTrashed()
            ->first();

        if (!$user) {
            return;
        }

        if ($user->deleted_at) {
            $fail('The user was soft deleted.');
            return;
        }

        if (!$user->hasAccess) {
            $fail('The user\'s access was restricted.');
            return;
        }

        if (!$eec = EventEventCategory::where('ref', $value)->first()) { // Check if the eec exists
            $fail('The event and category were not found.');
            return;
        }

        $result = Participant::isRegisteredToEEC($user, $eec);

        if ($result->status) {
            $fail($result->message);
            return;
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

    /**
     * Set the current validator.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
 
        return $this;
    }
}
