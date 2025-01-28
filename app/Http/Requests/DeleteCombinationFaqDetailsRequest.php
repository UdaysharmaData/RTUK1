<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteCombinationFaqDetailsRequest extends FormRequest
{
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
            'faq_details_ids' => [
                'required',
                'array'
            ],
            'faq_details_ids.*' => [
                Rule::exists('faq_details', 'id')
                    ->where('faq_id', $this->route('faq')?->id)
            ]
        ];
    }

    /**
     * Get custom validation messages that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function messages(): array
    {
        return [
            'faq_details_ids.*' => 'Invalid Page FAQ details id specified.'
        ];
    }
}
