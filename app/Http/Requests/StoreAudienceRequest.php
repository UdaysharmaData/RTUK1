<?php

namespace App\Http\Requests;

use App\Enums\AudienceSourceEnum;
use App\Rules\AudienceSourceData;
use App\Rules\DataUriImageSize;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Intervention\Validation\Rules\DataUri;

class StoreAudienceRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                Rule::unique('audiences')
                    ->where(fn ($query) => $query->where('site_id', clientSiteId()))
            ],
            'description' => ['sometimes', 'string'],
            'source' => ['required', new Enum(AudienceSourceEnum::class)],
            'data' => ['required', new AudienceSourceData($this->source ?? '')],
            'data.emails' => [
                Rule::requiredIf(fn() => $this->source === AudienceSourceEnum::Emails->value),
//                'sometimes',
                'array',
                'min:1',
                'max:100',
            ],
//            'data.emails.*' => ['email'],
            'data.mailing_list' => [
                Rule::requiredIf(fn() => $this->source === AudienceSourceEnum::MailingList->value),
                new DataUri(),
//                'sometimes',
//                'mimes:csv,txt'
            ],
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'source.Illuminate\Validation\Rules\Enum' => 'Invalid source specified',
            'name.unique' => 'The name already is assigned to another Audience on this platform.',
        ];
    }
}
