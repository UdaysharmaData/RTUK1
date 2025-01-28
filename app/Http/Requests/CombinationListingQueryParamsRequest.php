<?php

namespace App\Http\Requests;

use App\Enums\ListDraftedItemsOptionsEnum;
use Rule;
use App\Traits\SiteTrait;
use App\Enums\ListTypeEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\OrderByParamValidationClosure;
use App\Traits\FailedValidationResponseTrait;
use App\Enums\ListSoftDeletedItemsOptionsEnum;

class CombinationListingQueryParamsRequest extends FormRequest
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
            'category' => ['sometimes', 'nullable', Rule::exists('event_categories', 'ref')],
            'region' => [
                'sometimes', 'nullable', 'string', Rule::exists('regions', 'ref')
                    ->where(fn ($query) => $query->where("site_id", static::getSite()?->id))
            ],
            'city' => [
                'sometimes', 'nullable', 'string', Rule::exists('cities', 'ref')
                    ->where(fn ($query) => $query->where("site_id", static::getSite()?->id))
            ],
            'venue' => [
                'sometimes', 'nullable', 'string', Rule::exists('venues', 'ref')
                    ->where(fn ($query) => $query->where("site_id", static::getSite()?->id))
            ],
            'deleted' => ['sometimes', new Enum(ListSoftDeletedItemsOptionsEnum::class)],
            'drafted' => ['sometimes', new Enum(ListDraftedItemsOptionsEnum::class)],
            'order_by' => ['sometimes', 'array'],
            'order_by.*' => ['string', $this->isValidOrderByParameter(ListTypeEnum::Combinations)],
            'term' => ['sometimes', 'string', 'max:50'],
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
