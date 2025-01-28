<?php

namespace App\Http\Requests;

use App\Models\FaqCategory;
use Illuminate\Validation\Rule;
use App\Enums\FaqCategoryTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class StoreFaqCategoryRequest extends FormRequest
{
    use FailedValidationResponseTrait;

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
            'type' => [
                'required',
                'string',
                Rule::in(FaqCategoryTypeEnum::options())
            ],
            ...FaqCategory::RULES['create_or_update']
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'type.*' => 'Invalid category selected.'
        ];
    }
}
