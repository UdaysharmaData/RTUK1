<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Enum;
use App\Enums\ListingFaqsFilterOptionsEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class RegionAllQueryParamsRequest extends FormRequest
{
    use FailedValidationResponseTrait;

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
            'faqs' => ['sometimes', new Enum(ListingFaqsFilterOptionsEnum::class)],
            'term' => ['sometimes', 'nullable', 'string'],
            'country' => ['sometimes', 'nullable', 'string'],
            ...(new DefaultListingQueryParamsRequest())->rules()
        ];
    }
}
