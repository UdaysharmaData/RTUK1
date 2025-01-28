<?php

namespace App\Modules\Participant\Requests;

use Rule;
use App\Traits\SiteTrait;
use App\Enums\GenderEnum;
use App\Enums\ListTypeEnum;
use App\Enums\EventStateEnum;
use App\Enums\TimeReferenceEnum;
use App\Enums\ParticipantStatusEnum;
use App\Enums\ParticipantAddedViaEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use App\Traits\OrderByParamValidationClosure;
use App\Enums\ListSoftDeletedItemsOptionsEnum;
use App\Enums\ParticipantPaymentStatusEnum;
use App\Enums\ParticipantProfileTshirtSizeEnum;
use App\Enums\ParticipantWaiveEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Modules\Charity\Models\Charity;

class ParticipantListingQueryParamsRequest extends FormRequest
{
    use FailedValidationResponseTrait, OrderByParamValidationClosure, SiteTrait;

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
            'status' => ['sometimes', 'nullable', new Enum(ParticipantStatusEnum::class)],
            'state' => ['sometimes', 'nullable', new Enum(EventStateEnum::class)],
            'gender' => ['sometimes', 'nullable', new Enum(GenderEnum::class)],
            'tshirt_size' => ['sometimes', 'nullable', new Enum(ParticipantProfileTshirtSizeEnum::class)],
            'event' => ['sometimes', 'nullable', Rule::exists('events', 'ref')],
            'category' => ['sometimes', 'nullable', Rule::exists('event_categories', 'ref')],
            'charity' => ['sometimes', 'nullable', Rule::exists('charities', 'ref')],
            'year' => ['sometimes', 'nullable', 'digits:4', 'date_format:Y'],
            'month' => ['sometimes', 'nullable', 'numeric', 'min:1', 'max:12'],
            'via' => ['sometimes', 'nullable', new Enum(ParticipantAddedViaEnum::class)],
            'payment_status' => ['sometimes', new Enum(ParticipantPaymentStatusEnum::class)],
            'waive' => [ // Prohibited when the payment_status is not waived
                Rule::prohibitedIf($this->payment_status && ($this->payment_status == ParticipantPaymentStatusEnum::Paid->value || $this->payment_status == ParticipantPaymentStatusEnum::Unpaid->value)),
                new Enum(ParticipantWaiveEnum::class)
            ],
            'waiver' => [ // Prohibited when payment_status is not waived
                Rule::prohibitedIf($this->payment_status && ($this->payment_status == ParticipantPaymentStatusEnum::Paid->value || $this->payment_status == ParticipantPaymentStatusEnum::Unpaid->value)),
                new Enum(ParticipantWaiverEnum::class)
            ],
            'period' => ['sometimes', Rule::in(TimeReferenceEnum::values())],
            'deleted' => ['sometimes', new Enum(ListSoftDeletedItemsOptionsEnum::class)],
            'order_by' => ['sometimes', 'array'],
            'order_by.*' => ['string', $this->isValidOrderByParameter(ListTypeEnum::Participants)],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'waive.prohibited' => 'The waive field is prohibited when payment status is not waived.',
            'waiver.prohibited' => 'The waiver field is prohibited when payment status is not waived.',
        ];
    }

    public function bodyParameters()
    {
        return [
            'charity' => [
                'description' => "The ref of the charity. Must be one of ".implode(', ', Charity::inRandomOrder()->limit(3)->pluck('ref')->all()),
                'example' => Charity::inRandomOrder()
                    ->value('ref')
            ],
            'payment_status' => [
                'description' => "Must be one of ".implode(', ', array_column(ParticipantPaymentStatusEnum::cases(), 'value')),
                'example' => ParticipantPaymentStatusEnum::Unpaid->value
            ],
            'waive' => [
                'description' => "Must be one of ".implode(', ', array_column(ParticipantWaiveEnum::cases(), 'value')) . " . Is prohibited when payment_status is present and its value is not waived",
                'example' => ParticipantWaiveEnum::Completely->value
            ],
            'waiver' => [
                'description' => "Must be one of ".implode(', ', array_column(ParticipantWaiverEnum::cases(), 'value')) . " . Is prohibited when payment_status is present and its value is not waived",
                'example' => ParticipantWaiverEnum::Charity->value
            ]
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->_prepareForValidation();
    }

    /**
     * @return void
     */
    protected function passedValidation(): void
    {
        $this->_passedValidation();
    }
}
