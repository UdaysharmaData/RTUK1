<?php

namespace App\Modules\Finance\Requests;

use Auth;
use Rule;
use Carbon\Carbon;
use App\Modules\Setting\Enums\SiteEnum;
use App\Traits\SiteTrait;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use App\Modules\Finance\Enums\TransactionPaymentMethodEnum;

class PaymentMethodCreateRequest extends FormRequest
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
            'type' => ['required', new Enum(TransactionPaymentMethodEnum::class)],
            'exp_month' => [
                'date_format:m',
                Rule::requiredIf($this->type && $this->type == TransactionPaymentMethodEnum::Card->value),
                Rule::prohibitedIf($this->type && ($this->type != TransactionPaymentMethodEnum::Card->value)),
            ],
            'exp_year' => [
                'date_format:Y',
                Rule::requiredIf($this->type && $this->type == TransactionPaymentMethodEnum::Card->value),
                Rule::prohibitedIf($this->type && ($this->type != TransactionPaymentMethodEnum::Card->value)),
            ],
            'number' => [
                // 'string',
                'digits:16',
                Rule::requiredIf($this->type && $this->type == TransactionPaymentMethodEnum::Card->value),
                Rule::prohibitedIf($this->type && ($this->type != TransactionPaymentMethodEnum::Card->value)),
            ],
            'cvc' => [
                // 'string',
                'digits:3',
                Rule::requiredIf($this->type && $this->type == TransactionPaymentMethodEnum::Card->value),
                Rule::prohibitedIf($this->type && ($this->type != TransactionPaymentMethodEnum::Card->value)),
            ],

            'account_number' => [
                'string',
                Rule::requiredIf($this->type && $this->type == TransactionPaymentMethodEnum::BacsDebit->value),
                Rule::prohibitedIf($this->type && ($this->type != TransactionPaymentMethodEnum::BacsDebit->value)),
            ],
            'sort_code' => [
                'string',
                Rule::requiredIf($this->type && $this->type == TransactionPaymentMethodEnum::BacsDebit->value),
                Rule::prohibitedIf($this->type && ($this->type != TransactionPaymentMethodEnum::BacsDebit->value)),
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'exp_month.required' => 'The exp_month field is required when type is card.',
            'exp_year.required' => 'The exp_year field is required when type is card.',
            'number.required' => 'The number field is required when type is card.',
            'cvc.required' => 'The cvc field is required when type is card.',
            'account_number.required' => 'The account_number field is required when type is card.',
            'sort_code.required' => 'The sort_code field is required when type is card.',
            'exp_month.prohibited' => 'The exp_month field is prohibited when type is not card.',
            'exp_year.prohibited' => 'The exp_year field is prohibited when type is not card.',
            'number.prohibited' => 'The number field is prohibited when type is not card.',
            'cvc.prohibited' => 'The cvc field is prohibited when type is not card.',
            'account_number.prohibited' => 'The account_number field is prohibited when type is not bacs_debit.',
            'sort_code.prohibited' => 'The sort_code field is prohibited when type is not bacs_debit.',
        ];
    }

    public function bodyParameters()
    {
        return [
            'type' => [
                'description' => "Must be one of ".implode(', ', array_column(TransactionPaymentMethodEnum::cases(), 'value')) . ".",
                'example' => TransactionPaymentMethodEnum::Card->value
            ],
            'exp_month' => [
                'example' => random_int(1, 10)
            ],
            'exp_year' => [
                'example' => Carbon::now()->addYear()
            ],
            'number' => [
                'example' => 424242424242
            ],
            'cvc' => [
                'example' => 123
            ],
            'account_number' => [
                'example' => 00012345
            ],
            'sort_code' => [
                'example' => 10-20-30
            ]
        ];
    }
}
