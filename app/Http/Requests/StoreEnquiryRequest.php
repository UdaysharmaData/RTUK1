<?php

namespace App\Http\Requests;

use App\Enums\EnquiryTypeEnum;
use App\Models\ClientEnquiry;
use App\Services\ClientOptions\EnquirySettings;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class StoreEnquiryRequest extends FormRequest
{
    use FailedValidationResponseTrait;

    /**
     * @var array|mixed
     */
    private mixed $categories;

    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);

        $this->categories = (new EnquirySettings())->categories();
    }

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
    public function rules(): array
    {
        return [
            'enquiry_type' => [
                'required',
                'string',
                $this->isValidCategory()
            ],
            ...ClientEnquiry::RULES['create_or_update']
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'enquiry_type.*' => 'Invalid Inquiry Type selected.'
        ];
    }

    /**
     * @return array
     */
    private function validCategories(): array
    {
        return $this->categories;
    }

    /**
     * @return \Closure
     */
    private function isValidCategory(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if (! in_array((string) $value, $this->validCategories())) $fail($this->messages()['enquiry_type.*']);
        };
    }
}
