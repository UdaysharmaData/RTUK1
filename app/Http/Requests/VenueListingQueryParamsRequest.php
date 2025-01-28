<?php

namespace App\Http\Requests;

use App\Enums\ListDraftedItemsOptionsEnum;
use App\Enums\ListTypeEnum;
use App\Rules\HasSiteAccess;
use Illuminate\Validation\Rules\Enum;
use App\Enums\ListingFaqsFilterOptionsEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\OrderByParamValidationClosure;
use App\Traits\FailedValidationResponseTrait;
use App\Enums\ListSoftDeletedItemsOptionsEnum;

class VenueListingQueryParamsRequest extends FormRequest
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
            'city' => ['sometimes', 'nullable', 'exists:cities,ref', new HasSiteAccess()],
            'region' => ['sometimes', 'nullable', 'exists:regions,ref', new HasSiteAccess()],
            'country' => ['sometimes', 'nullable', 'string'],
            'faqs' => ['sometimes', new Enum(ListingFaqsFilterOptionsEnum::class)],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'deleted' => ['sometimes', new Enum(ListSoftDeletedItemsOptionsEnum::class)],
            'order_by' => ['sometimes', 'array'],
            'order_by.*' => ['string', $this->isValidOrderByParameter(ListTypeEnum::EventPropertyServices)],
            'drafted' => ['sometimes', new Enum(ListDraftedItemsOptionsEnum::class)],
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
