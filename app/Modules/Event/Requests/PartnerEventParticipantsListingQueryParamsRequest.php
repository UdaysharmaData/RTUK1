<?php

namespace App\Modules\Event\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\OrderByParamValidationClosure;
use App\Traits\FailedValidationResponseTrait;

class PartnerEventParticipantsListingQueryParamsRequest extends FormRequest
{
    use FailedValidationResponseTrait, OrderByParamValidationClosure;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'term' => ['sometimes', 'nullable', 'string'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer'],
            'site' => ['sometimes', 'nullable', 'exists:sites,ref'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() 
    {
        return [];
    }
}
