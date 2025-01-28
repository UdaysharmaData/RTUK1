<?php

namespace App\Modules\Enquiry\Requests;

use Auth;
use Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use Illuminate\Http\Exceptions\HttpResponseException;

class ExternalEnquiryDeleteRequest extends FormRequest
{
    use FailedValidationResponseTrait;

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
        return [
            'refs' => ['required', 'array', Rule::exists('external_enquiries', 'ref')],
            'refs.*' => ['string']
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [

        ];
    }

    public function bodyParameters()
    {
        return [
            'refs' => [
                'description' => 'The ref of the external enquiry(ies). Can be a string or an array of external enquiries refs. Must be one of '. implode(', ', ExternalEnquiry::inRandomOrder()->limit(6)->pluck('ref')->toArray()),
                'example' => ExternalEnquiry::inRandomOrder()->limit(6)->pluck('ref')->toArray()
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'refs' => $this->refs 
                ? (
                    is_array($this->refs) // Cast string to array if it's not an array
                        ? $this->refs
                        : collect($this->refs)->toArray()
                    )
                :
                null
        ]);
    }
}