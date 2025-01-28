<?php

namespace App\Modules\Event\Requests;

use Auth;
use Rule;

use Illuminate\Validation\Rules\Enum;
use App\Enums\EventCustomFieldTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class EventCustomFieldUpdateRequest extends FormRequest
{
    use FailedValidationResponseTrait;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', new Enum(EventCustomFieldTypeEnum::class)],
            'caption' => ['nullable'],
            'possibilities' => ['nullable'],
            'possibilities.options' => [
                'array',
                'required_with:possibilities.values',
                'size:'.(isset($this->possibilities) && isset($this->possibilities['values']) ? count($this->possibilities['values']) : 1),
                Rule::requiredIf($this->type == EventCustomFieldTypeEnum::Select->value)
            ],
            'possibilities.options.*' => [
                'string',
            ],
            'possibilities.values' => [
                'array',
                'required_with:possibilities.options',
                'size:'.(isset($this->possibilities) && isset($this->possibilities['options']) ? count($this->possibilities['options']) : 1),
                Rule::requiredIf($this->type ==  EventCustomFieldTypeEnum::Select->value)
            ],
            'possibilities.values.*' => [
                'string'
            ],
            'status' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'possibilities.options.required' => 'The options field is required when the type is select.',
            'possibilities.values.required' => 'The values field is required when the type is select.',
            'possibilities.options.size' => 'The number of options and values items does not match.',
            'possibilities.values.size' => 'The number of options and values items does not match.',
            'possibilities.options.required_with' => 'The options field is required when values field is present.',
            'possibilities.values.required_with' => 'The values field is required when options field is present.'
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The custom field name',
                'example' => 'Marital Status',
            ],
            'caption' => [
                'description' => 'The custom field caption',
            ],
            'type' => [
                'description' => "The custom field response type. Must be one of ".implode(', ', array_column(EventCustomFieldTypeEnum::cases(), 'value')),
                'example' => array_column(EventCustomFieldTypeEnum::cases(), 'value')[2]
            ],
            'possibilities.options' => [
                'example' => ['Tiger', 'Lion']
            ],
            'possibilities.values' => [
                'example' => ['tiger', 'lion']
            ],
        ];
    }
}
