<?php

namespace App\Http\Requests;

use Rule;
use App\Enums\MonthEnum;
use App\Enums\ListTypeEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\TimeReferenceEnum;
use App\Enums\InvoiceItemTypeEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\OrderByParamValidationClosure;
use App\Traits\FailedValidationResponseTrait;
use App\Enums\ListSoftDeletedItemsOptionsEnum;

class InvoiceListingQueryParamsRequest extends FormRequest
{
    use FailedValidationResponseTrait, OrderByParamValidationClosure;

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
            'type' => ['sometimes', 'nullable', new Enum(InvoiceItemTypeEnum::class)],
            'status' => ['sometimes', 'nullable', new Enum(InvoiceStatusEnum::class)],
            'held' => ['sometimes', 'nullable', 'boolean'],
            'year' => ['sometimes', 'nullable', 'digits:4', 'date_format:Y'],
            'month' => ['sometimes', 'nullable', new Enum(MonthEnum::class)],
            'price' => ['sometimes', 'nullable', 'array', 'size:2'],
            'price.*' => ['integer'],
            'term' => ['sometimes', 'nullable', 'string'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'period' => ['sometimes', Rule::in(TimeReferenceEnum::values())],
            'deleted' => ['sometimes', new Enum(ListSoftDeletedItemsOptionsEnum::class)],
            'order_by' => ['sometimes', 'array'],
            'order_by.*' => ['string', $this->isValidOrderByParameter(ListTypeEnum::Invoices)],
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
