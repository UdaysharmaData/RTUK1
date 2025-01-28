<?php

namespace App\Modules\Partner\Requests;

use Rule;
use App\Enums\ListTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\OrderByParamValidationClosure;
use App\Traits\FailedValidationResponseTrait;

class PartnerChannelListingQueryParamsRequest extends FormRequest
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
            'site' => ['sometimes', 'nullable', 'exists:sites,ref'],
            'partner' => ['sometimes', 'nullable', Rule::exists('partners', 'ref')],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'order_by' => ['sometimes', 'array'],
            'order_by.*' => ['string', $this->isValidOrderByParameter(ListTypeEnum::PartnerChannels)],
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
