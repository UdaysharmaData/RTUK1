<?php

namespace App\Modules\Event\Requests;

use App\Enums\ListDraftedItemsOptionsEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

use App\Enums\ListTypeEnum;
use App\Traits\FailedValidationResponseTrait;
use App\Traits\OrderByParamValidationClosure;
use App\Enums\ListSoftDeletedItemsOptionsEnum;

class SponsorListingQueryParamsRequest extends FormRequest
{
    use FailedValidationResponseTrait, OrderByParamValidationClosure;

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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'term' => ['sometimes', 'nullable', 'string'],
            'popular' => ['sometimes', 'nullable', 'boolean'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'drafted' => ['sometimes', new Enum(ListDraftedItemsOptionsEnum::class)],
            'deleted' => ['sometimes', new Enum(ListSoftDeletedItemsOptionsEnum::class)],
            'order_by' => ['sometimes', 'array'],
            'order_by.*' => ['string', $this->isValidOrderByParameter(ListTypeEnum::EventPropertyServices)],
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
