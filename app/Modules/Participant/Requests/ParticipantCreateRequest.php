<?php

namespace App\Modules\Participant\Requests;

use Auth;
use App\Traits\SiteTrait;
use App\Rules\IsRegisteredToEEC;
use App\Http\Requests\PaymentRequest;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class ParticipantCreateRequest extends FormRequest
{
    use FailedValidationResponseTrait,
        SiteTrait;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            // Validate user
            'email' => ['required', 'string', 'email', new IsRegisteredToEEC],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string']
        ];

        $rules = array_merge((new PaymentRequest($this->request->all()))->rules(), $rules);

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return (new PaymentRequest)->messages();
    }

    public function bodyParameters()
    {
        $bodyParameters = [
            'email' => [
                'example' => 'marc@runforcharity.com'
            ],
            'first_name' => [
                'example' => 'Marc'
            ],
            'last_name' => [
                'example' => 'Roby AM'
            ]
        ];

        $bodyParameters = array_merge((new PaymentRequest)->bodyParameters(), $bodyParameters);

        return $bodyParameters;
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'first_name' => ucwords(trim($this->first_name)),
            'last_name' => ucwords(trim($this->last_name)),
            'email' => trim($this->email)
        ]);
    }
}
