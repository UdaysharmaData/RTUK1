<?php

namespace App\Http\Requests;

use App\Enums\AudienceSourceEnum;
use App\Enums\ListDraftedItemsOptionsEnum;
use App\Enums\ListTypeEnum;
use App\Enums\RoleNameEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\OrderByParamValidationClosure;
use App\Traits\FailedValidationResponseTrait;
use App\Enums\ListSoftDeletedItemsOptionsEnum;

class AudienceListingQueryParamsRequest extends FormRequest
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
            'term' => ['sometimes', 'string', 'max:50'],
            'per_page' => ['sometimes', 'numeric', 'min:1'],
            'drafted' => ['sometimes', new Enum(ListDraftedItemsOptionsEnum::class)],
            'deleted' => ['sometimes', new Enum(ListSoftDeletedItemsOptionsEnum::class)],
            'order_by' => ['sometimes', 'array'],
            'order_by.*' => ['string', $this->isValidOrderByParameter(ListTypeEnum::Audiences)],
            'source' => ['sometimes', new Enum(AudienceSourceEnum::class)],
            'role' => ['sometimes', new Enum(RoleNameEnum::class)],
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

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'source.Illuminate\Validation\Rules\Enum' => 'Invalid source specified',
            'role.Illuminate\Validation\Rules\Enum' => 'Invalid role specified',
            'drafted.Illuminate\Validation\Rules\Enum' => 'Invalid drafted option specified',
            'deleted.Illuminate\Validation\Rules\Enum' => 'Invalid deleted option specified',
        ];
    }
}
