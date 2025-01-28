<?php

namespace App\Http\Requests;

use App\Enums\ListingFaqsFilterOptionsEnum;
use App\Enums\ListTypeEnum;
use App\Enums\PageStatus;
use App\Enums\RedirectStatusEnum;
use App\Enums\RedirectTypeEnum;
use App\Enums\TimeReferenceEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\OrderByParamValidationClosure;
use App\Traits\FailedValidationResponseTrait;
use App\Enums\ListSoftDeletedItemsOptionsEnum;

class RedirectListingQueryParamsRequest extends FormRequest
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
            'status' => ['sometimes', 'required', new Enum(RedirectStatusEnum::class)],
            'type' => ['sometimes', 'required', new Enum(RedirectTypeEnum::class)],
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'status.Illuminate\Validation\Rules\Enum' => 'Invalid status specified',
            'type.Illuminate\Validation\Rules\Enum' => 'Invalid type specified'
        ];
    }
}
