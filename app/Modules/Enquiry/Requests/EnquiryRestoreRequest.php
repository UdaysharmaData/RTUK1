<?php

namespace App\Modules\Enquiry\Requests;

use Auth;
use Rule;
use App\Traits\SiteTrait;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use App\Modules\Enquiry\Models\Enquiry;

class EnquiryRestoreRequest extends FormRequest
{
    use FailedValidationResponseTrait, SiteTrait;

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
            'refs' => ['required', 'array', Rule::exists('enquiries', 'ref')->onlyTrashed()],
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
                'description' => 'The refs of the enquiry(s). Can be a string or an array of enquiry(s) refs. Must be one of '. implode(', ', Enquiry::inRandomOrder()
                    ->whereHas('site', function ($query) {
                        $query->makingRequest();
                    })->limit(3)->pluck('ref')->toArray()),
                'example' => Enquiry::inRandomOrder()
                    ->whereHas('site', function ($query) {
                        $query->makingRequest();
                    })->limit(3)->pluck('ref')->toArray()
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
