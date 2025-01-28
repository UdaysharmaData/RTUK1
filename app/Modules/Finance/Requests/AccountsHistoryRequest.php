<?php

namespace App\Modules\Finance\Requests;

use App\Traits\SiteTrait;
use App\Enums\ListTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\OrderByParamValidationClosure;
use App\Traits\FailedValidationResponseTrait;
use App\Enums\ListSoftDeletedItemsOptionsEnum;
use App\Modules\Finance\Enums\AccountStatusEnum;

class AccountsHistoryRequest extends FormRequest
{
    use FailedValidationResponseTrait,
        SiteTrait,
        OrderByParamValidationClosure;

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
            'term' => ['sometimes', 'nullable', 'string'],
            'account' => ['sometimes', 'required', Rule::exists('accounts', 'ref')],
            'status' => ['sometimes', 'required', new Enum(AccountStatusEnum::class)],
            'deleted' => ['sometimes', new Enum(ListSoftDeletedItemsOptionsEnum::class)],
            'order_by' => ['sometimes', 'array'],
            'order_by.*' => ['string', $this->isValidOrderByParameter(ListTypeEnum::InternalTransactions)]
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [

        ];
    }

    public function bodyParameters()
    {
        return [
            'status' => [
                'description' => "Must be one of ".implode(', ', array_column(AccountStatusEnum::cases(), 'value')) . ".",
                'example' => AccountStatusEnum::Active->value
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
