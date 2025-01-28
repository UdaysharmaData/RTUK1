<?php

namespace App\Http\Requests;

use App\Contracts\ListingQueryParamsRequestContract;
use App\Enums\ListTypeEnum;
use App\Enums\PageStatus;
use App\Enums\RoleNameEnum;
use App\Enums\SiteUserStatus;
use App\Enums\UserVerificationStatus;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\OrderByParamValidationClosure;
use App\Traits\FailedValidationResponseTrait;
use App\Enums\ListSoftDeletedItemsOptionsEnum;

class UserListingQueryParamsRequest extends FormRequest implements ListingQueryParamsRequestContract
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
            'deleted' => ['sometimes', new Enum(ListSoftDeletedItemsOptionsEnum::class)],
            'order_by' => ['sometimes', 'array'],
            'order_by.*' => ['string', $this->isValidOrderByParameter(ListTypeEnum::Users)],
            'role' => ['sometimes', new Enum(RoleNameEnum::class)],
            'term' => ['sometimes', 'string', 'max:50'],
            'status' => ['sometimes', new Enum(SiteUserStatus::class)],
            'verification' => ['sometimes', new Enum(UserVerificationStatus::class)]
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
