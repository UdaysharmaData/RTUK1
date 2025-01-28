<?php

namespace App\Http\Requests;

use Auth;
use Rule;
use App\Models\Invoice;
use App\Traits\SiteTrait;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class InvoiceRestoreRequest extends FormRequest
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
            'refs' => ['required', 'array', Rule::exists('invoices', 'ref')->onlyTrashed()],
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
                'description' => 'The refs of the invoice(s). Can be a string or an array of invoices refs. Must be one of '. implode(', ', Invoice::inRandomOrder()
                    ->whereHas('site', function ($query) {
                        $query->makingRequest();
                    })->limit(3)->pluck('ref')->toArray()),
                'example' => Invoice::inRandomOrder()
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
