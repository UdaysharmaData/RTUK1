<?php

namespace App\Modules\Enquiry\Requests;

use Auth;
use App\Traits\SiteTrait;
use App\Http\Requests\PaymentRequest;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class ExternalEnquiryOfferPlaceRequest extends FormRequest
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
            'make_default' => ['sometimes', 'required', 'boolean']
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
        return (new PaymentRequest)->bodyParameters();
    }
}
