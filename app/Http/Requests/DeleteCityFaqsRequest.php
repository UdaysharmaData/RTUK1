<?php

namespace App\Http\Requests;

use App\Models\City;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteCityFaqsRequest extends FormRequest
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
            'faqs_ids' => [
                'required',
                'array'
            ],
            'faqs_ids.*' => [
                Rule::exists('faqs', 'id')
                    ->where('faqsable_type', City::class)
                    ->where('faqsable_id', $this->route('city')?->id)
                    ->where('site_id', clientSiteId())
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
            'faqs_ids.*' => 'Invalid City FAQ id specified.'
        ];
    }
}
