<?php

namespace App\Http\Requests;

use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeletePageFaqsRequest extends FormRequest
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
                    ->where('faqsable_type', Page::class)
                    ->where('faqsable_id', $this->route('page')?->id)
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
            'faqs_ids.*' => 'Invalid Page FAQ id specified.'
        ];
    }
}
