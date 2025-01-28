<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use App\Traits\ImageVersionsValidator;

class PaginationRequest extends FormRequest
{
    use FailedValidationResponseTrait, ImageVersionsValidator;

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
     * @return array
     */
    public function rules()
    {
        return [
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            ...$this->imageVersionsRules()
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'page.integer' => 'The page number must be an integer',
            'page.min' => 'The page number must be at least 1',
            'per_page.integer' => 'The number of items per page must be an integer',
            'per_page.min' => 'The number of items per page must be at least 1',
            ...$this->imageVersionsMessages()
        ];
    }

    public function bodyParameters()
    {
        return [
            'page' => [
                'title' => 'page',
                'example' => 1
            ],
            'per_page' => [
                'title' => 'per page',
                'example' => 10
            ],
            ...$this->imageVersionsBodyParameters()
        ];
    }
}
