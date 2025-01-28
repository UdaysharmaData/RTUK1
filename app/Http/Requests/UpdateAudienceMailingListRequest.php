<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAudienceMailingListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
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
            'first_name' => ['sometimes', 'string'],
            'last_name' => ['sometimes', 'string'],
            'email' => ['sometimes', 'string', 'email', 'max:255',
                Rule::unique('mailing_lists')
                    ->where('site_id', clientSiteId())
                    ->where('audience_id', $this->route('audience')?->id)
                    ->ignore($this->route('mailingList')?->id),
            ],
            'phone' => ['sometimes', 'string']
        ];
    }
}
