<?php

namespace App\Modules\Event\Requests;

use Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

use App\Traits\SiteTrait;
use App\Enums\EventStateEnum;
use App\Modules\Event\Models\Event;
use App\Enums\EventCategoryVisibilityEnum;
use App\Traits\OrderByParamValidationClosure;
use App\Traits\FailedValidationResponseTrait;
use App\Traits\AttributeExistsInModelValidator;

class EventAllQueryParamsRequest extends FormRequest
{
    use FailedValidationResponseTrait, OrderByParamValidationClosure, SiteTrait, AttributeExistsInModelValidator;

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
            'with' => ['sometimes', 'required', 'array:value,visibility'],
            'with.value' => [
                'in:categories,third_parties',
                'string',
                Rule::requiredIf($this->with == true)
            ],
            'with.visibility' => [
                'sometimes',
                'required',
                'string',
                new Enum(EventCategoryVisibilityEnum::class),
                // Rule::prohibitedIf($this->filled('with') && $this->filled('with.value') && $this->with['value'] != 'categories'), // Filter by visibility for both categories & third_parties
            ],
            'active' => ['sometimes', 'nullable', 'boolean'],
            'state' => ['sometimes', 'nullable', new Enum(EventStateEnum::class)],
            ...$this->attributeExistsInModelValidatorRule(new Event()),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() 
    {
        return [
            'with.required' => 'The with field is required when it is present.',
            'with.value.required' => 'The value field is required when with is present.',
            'with.value.Illuminate\Validation\Rules\Enum' => 'The selected value is invalid.',
            'with.visibility.required' => 'The visibility field is required when it is present.',
            'with.visibility.Illuminate\Validation\Rules\Enum' => 'The selected visibility is invalid.',
            'with.visibility.prohibited' => 'The visibility field is prohibited when value field is not categories.'
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
        $this->attributeExistsInModelValidatorPrepareForValidation();
    }

    /**
     * @return void
     */
    protected function passedValidation(): void
    {
        $this->_passedValidation();
        $this->attributeExistsInModelValidatorPassedValidation(new Event());
    }
}
