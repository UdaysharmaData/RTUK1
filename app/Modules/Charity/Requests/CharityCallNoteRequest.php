<?php

namespace App\Modules\Charity\Requests;

use App\Enums\CallNoteCallEnum;
use App\Enums\CallNoteStatusEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class CharityCallNoteRequest extends FormRequest
{
    use FailedValidationResponseTrait;

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
            'note' => ['nullable', 'string'],
            'call' => ['required', new Enum(CallNoteCallEnum::class)],
            'status' => ['required', new Enum(CallNoteStatusEnum::class)],
            'year' => ['required', 'digits:4', 'date_format:Y', 'after_or_equal:'.date('Y')],
        ];
    }

    public function bodyParameters()
    {
        return [
            'note' => [
                'description' => 'The message.',
                'example' => '26/07 - Spoke to Fru and he is chasing the invoice payment'
            ],
            'call' => [
                'description' => 'Must be one of 23_months, 21_months, 18_months, 15_months, 12_months, 11_months, 
                8_months, 5_months, 2_months or 1_month',
                'example' => '23_months'
            ],
            'status' => [
                'description' => 'Must be one of made_contact, no_answer',
                'example' => 'no_answer'
            ],
            'year' => [
                'description' => 'The year the call note was created.',
                'example' => 2022
            ],
        ];
    }
}
