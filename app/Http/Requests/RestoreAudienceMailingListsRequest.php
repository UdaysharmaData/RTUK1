<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RestoreAudienceMailingListsRequest extends FormRequest
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
            'mailing_lists_ids' => ['required', 'array'],
            'mailing_lists_ids.*' => [
                Rule::exists('mailing_lists', 'id')
                    ->where('site_id', clientSiteId())
                    ->where('audience_id', $this->route('audience')?->id)
            ]
        ];
    }
}
