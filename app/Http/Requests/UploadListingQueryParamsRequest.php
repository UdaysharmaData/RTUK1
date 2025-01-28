<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

use App\Enums\UploadTypeEnum;
use App\Traits\FailedValidationResponseTrait;
use App\Contracts\ListingQueryParamsRequestContract;
use App\Enums\ListTypeEnum;
use App\Traits\OrderByParamValidationClosure;

class UploadListingQueryParamsRequest  extends FormRequest implements ListingQueryParamsRequestContract
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
            'per_page' => ['sometimes', 'numeric', 'min:1'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'type' => ['sometimes', new Enum(UploadTypeEnum::class)],
            'year' => ['sometimes', 'nullable', 'digits:4', 'date_format:Y'],
            'term' => ['sometimes', 'nullable', 'string'],
            'order_by' => ['sometimes', 'array'],
            'order_by.*' => ['string', $this->isValidOrderByParameter(ListTypeEnum::Uploads)],
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
