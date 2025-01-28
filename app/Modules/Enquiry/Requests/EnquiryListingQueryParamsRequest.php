<?php

namespace App\Modules\Enquiry\Requests;

use Rule;
use App\Enums\ListTypeEnum;
use App\Enums\EnquiryStatusEnum;
use App\Enums\EnquiryActionEnum;
use App\Enums\TimeReferenceEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use App\Traits\OrderByParamValidationClosure;
use App\Enums\ListSoftDeletedItemsOptionsEnum;

class EnquiryListingQueryParamsRequest extends FormRequest
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
            'term' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', new Enum(EnquiryStatusEnum::class)],
            'action' => ['sometimes', 'nullable', new Enum(EnquiryActionEnum::class)],
            'site' => ['sometimes', 'nullable', 'exists:sites,ref'],
            'year' => ['sometimes', 'nullable', 'digits:4', 'date_format:Y'],
            'month' => ['sometimes', 'nullable', 'numeric', 'min:1', 'max:12'],
            'charity' => ['sometimes', 'nullable', Rule::exists('charities', 'ref')],
            'event' => ['sometimes', 'nullable', Rule::exists('events', 'ref')],
            'converted' => ['sometimes', 'nullable', 'boolean'],
            'contacted' => ['sometimes', 'nullable', 'boolean'],
            'period' => ['sometimes', Rule::in(TimeReferenceEnum::values())],
            'deleted' => ['sometimes', new Enum(ListSoftDeletedItemsOptionsEnum::class)],
            'order_by' => ['sometimes', 'array'],
            'order_by.*' => ['string', $this->isValidOrderByParameter(ListTypeEnum::Enquiries)],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
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
