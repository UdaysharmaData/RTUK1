<?php

namespace App\Modules\Finance\Requests;

use Rule;
use App\Traits\SiteTrait;
use App\Rules\IsActiveEvent;
use App\Http\Helpers\AccountType;
use Illuminate\Validation\Rules\Enum;
use App\Enums\ParticipantAddedViaEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use App\Modules\Event\Models\EventEventCategory;

class ParticipantTransferPostPaymentPayloadRequest extends FormRequest
{
    use FailedValidationResponseTrait, SiteTrait;

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
     * @return array
     */
    public function rules()
    {
        return [
            'eec' => ['required', 'array'],
            'eec.*.ref' => [
                'bail',
                'required',
                'string'
            ],
            'added_via' => ['required', new Enum(ParticipantAddedViaEnum::class)],
            'participant' => ['required', 'exists:participants,ref'],
            'save_payment_method' => ['sometimes', 'required', 'boolean'],
            'default_payment_method' => ['sometimes', 'required', 'boolean']
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'eec.*.ref.string' => 'The selected category must be a string.',
            'eec.*.ref.required' => 'The selected category is required.',
            'eec.*.ref.exists' => 'The selected category is invalid.',

            // 'eec.*.ref.required' => [
            //     'value' => ':input',
            //     'message' => 'The selected category is required.'
            // ],
            // 'eec.*.ref.exists' => [
            //     'value' => ':input',
            //     'message' => 'The selected category is invalid.'
            // ]
        ];
    }

    public function bodyParameters()
    {
        return [
            'eec.*.ref' => [
                'description' => '(required)',
                'example' => EventEventCategory::inRandomOrder()->value('ref')
            ]
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // TODO: @tsaffi: Ensure the required properties are present and have some specific values. Example: added_via should be present and have transfer as value.
    }
}
