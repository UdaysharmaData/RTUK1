<?php

namespace App\Http\Requests;

use App\Enums\ListDraftedItemsOptionsEnum;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

use App\Enums\ListTypeEnum;
use App\Enums\MedalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\OrderByParamValidationClosure;
use App\Enums\ListSoftDeletedItemsOptionsEnum;

class MedalListingQueryParamsRequest extends FormRequest
{
    use OrderByParamValidationClosure;

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
            'drafted' => ['sometimes', new Enum(ListDraftedItemsOptionsEnum::class)],
            'deleted' => ['sometimes', new Enum(ListSoftDeletedItemsOptionsEnum::class)],
            'term' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', 'nullable', new Enum(MedalTypeEnum::class)],
            'event' => ['sometimes', 'nullable', Rule::exists('events', 'slug')],
            'category' => ['sometimes', 'nullable', Rule::exists('event_categories', 'slug')],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'order_by' => ['sometimes', 'array'],
            'order_by.*' => ['string', $this->isValidOrderByParameter(ListTypeEnum::Medals)],
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
