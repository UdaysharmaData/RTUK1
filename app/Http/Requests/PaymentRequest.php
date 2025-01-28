<?php

namespace App\Http\Requests;

use Auth;
use Rule;
use App\Traits\SiteTrait;
use App\Rules\IsActiveEvent;
use App\Http\Helpers\AccountType;
use Illuminate\Validation\Rules\Enum;
use App\Modules\Charity\Models\Charity;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\IsEventEventCategoryFeeNotNull;
use App\Traits\FailedValidationResponseTrait;
use App\Modules\Event\Models\EventEventCategory;

use App\Enums\FeeTypeEnum;
use App\Enums\ParticipantWaiveEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Enums\ParticipantPaymentStatusEnum;

class PaymentRequest extends FormRequest
{
    use FailedValidationResponseTrait,
        SiteTrait;

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
            'eec' => ['bail', 'required', Rule::exists('event_event_category', 'ref')->where(function ($query) { // Ensure the site has access to the event having the eec
                if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                    $query->whereIn('event_event_category.event_category_id', function($query) {
                        $query->select('event_categories.id')
                            ->from('event_categories')
                            ->whereIn('event_categories.site_id', function ($query) {
                                $query->select('sites.id')
                                    ->from('sites')
                                    ->where('sites.id', static::getSite()?->id);
                            });
                    });
                }
            }), new IsActiveEvent],
            'charity' => [
                'exists:charities,ref',
                Rule::requiredIf($this->waiver && $this->waiver == ParticipantWaiverEnum::Charity->value)
            ],
            'payment_status' => ['required', new Enum(ParticipantPaymentStatusEnum::class), function ($attribute, $value, $fail) {
                if (($value == ParticipantPaymentStatusEnum::Paid->value) && ! AccountType::isAdmin()) { // Ensure only the admin can set the paid payment status
                    $fail('Only the admin can set the payment status to paid');
                }
            }],
            'waive' => [ // Required when the participant is exempted (partially or fully) from payment
                Rule::requiredIf($this->payment_status && $this->payment_status == ParticipantPaymentStatusEnum::Waived->value),
                Rule::prohibitedIf($this->payment_status && ($this->payment_status == ParticipantPaymentStatusEnum::Paid->value || $this->payment_status == ParticipantPaymentStatusEnum::Unpaid->value)),
                new Enum(ParticipantWaiveEnum::class)
            ],
            'waiver' => [ // Required when the participant is exempted (partially or fully) from payment
                Rule::requiredIf($this->payment_status && $this->payment_status == ParticipantPaymentStatusEnum::Waived->value),
                Rule::prohibitedIf($this->payment_status && ($this->payment_status == ParticipantPaymentStatusEnum::Paid->value || $this->payment_status == ParticipantPaymentStatusEnum::Unpaid->value)),
                new Enum(ParticipantWaiverEnum::class)
            ],
            'fee_type' => [
                'bail',
                Rule::requiredIf($this->payment_status && $this->payment_status == ParticipantPaymentStatusEnum::Paid->value),
                Rule::prohibitedIf($this->payment_status && $this->payment_status != ParticipantPaymentStatusEnum::Paid->value),
                new Enum(FeeTypeEnum::class),
                new IsEventEventCategoryFeeNotNull
            ]
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'charity.required' => 'The charity field is required when waiver is charity.',
            'waive.required' => 'The waive field is required when payment status is waived.',
            'waiver.required' => 'The waiver field is required when payment status is waived.',
            'waive.prohibited' => 'The waive field is prohibited when payment status is not waived.',
            'waiver.prohibited' => 'The waiver field is prohibited when payment status is not waived.',
            'fee_type.required' => 'The fee type field is required when payment status is paid.',
            'fee_type.prohibited' => 'The fee type field is prohibited when payment status is not paid.'
        ];
    }

    public function bodyParameters()
    {
        return [
            'eec' => [
                'description' => "The ref of the event event category. Must be one of ".implode(', ', EventEventCategory::inRandomOrder()->limit(3)->pluck('ref')->all()),
                'example' => EventEventCategory::inRandomOrder()
                    ->value('ref')
            ],
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
                'description' => "In case the participant is exempted (partially or fully) from payment. Is required and should be present when payment_status is ".ParticipantPaymentStatusEnum::Waived->value.". Must be one of ".implode(', ', array_column(ParticipantWaiveEnum::cases(), 'value')),
                'example' => ParticipantWaiveEnum::Completely->value
            ],
            'waiver' => [
                'description' => "The partner waiving or offering the place (In case the participant is exempted (partially or fully) from payment). Is required and should be present when payment_status is ".ParticipantPaymentStatusEnum::Waived->value.". Must be one of ".implode(', ', array_column(ParticipantWaiverEnum::cases(), 'value')),
                'example' => ParticipantWaiverEnum::Charity->value
            ],
            'fee_type' => [
                'description' => "The type of fee paid by the participant. Is required and should be present when payment_status is ".ParticipantPaymentStatusEnum::Paid->value.". Must be one of ".implode(', ', array_column(FeeTypeEnum::cases(), 'value')),
                'example' => FeeTypeEnum::Local->value
            ]
        ];
    }
}
