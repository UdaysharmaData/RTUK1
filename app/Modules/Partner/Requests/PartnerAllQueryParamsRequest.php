<?php

namespace App\Modules\Partner\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class PartnerAllQueryParamsRequest extends FormRequest
{
    use FailedValidationResponseTrait;

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
            'with' => ['sometimes', 'nullable', 'in:channels', 'string'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ];
    }
}
