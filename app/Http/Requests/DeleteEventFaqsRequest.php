<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

use App\Modules\Event\Models\Event;

class DeleteEventFaqsRequest extends FormRequest
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
                    ->where('faqsable_type', Event::class)
                    ->where('faqsable_id', $this->route('event')?->id)
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
            'faqs_ids.*' => 'Invalid Event FAQ id specified.'
        ];
    }
}
