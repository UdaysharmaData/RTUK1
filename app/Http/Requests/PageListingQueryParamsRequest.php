<?php

namespace App\Http\Requests;

use App\Enums\ListDraftedItemsOptionsEnum;
use App\Enums\ListingFaqsFilterOptionsEnum;
use App\Enums\ListTypeEnum;
use App\Enums\PageStatus;
use App\Enums\TimeReferenceEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\OrderByParamValidationClosure;
use App\Traits\FailedValidationResponseTrait;
use App\Enums\ListSoftDeletedItemsOptionsEnum;

class PageListingQueryParamsRequest extends FormRequest
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
            'period' => ['sometimes', 'string', new Enum(TimeReferenceEnum::class)],
            'meta_keywords' => ['sometimes', 'string'],
            'faqs' => ['sometimes', 'string', new Enum(ListingFaqsFilterOptionsEnum::class)],
            'term' => ['sometimes', 'string', 'max:50'],
            'year' => ['sometimes', 'string', 'max:4'],
            'status' => ['sometimes', new Enum(PageStatus::class)],
            'per_page' => ['sometimes', 'numeric', 'min:1'],
            'drafted' => ['sometimes', new Enum(ListDraftedItemsOptionsEnum::class)],
            'deleted' => ['sometimes', new Enum(ListSoftDeletedItemsOptionsEnum::class)],
            'order_by' => ['sometimes', 'array'],
            'order_by.*' => ['string', $this->isValidOrderByParameter(ListTypeEnum::Pages)],
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
