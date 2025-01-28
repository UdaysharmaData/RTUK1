<?php

namespace App\Modules\Event\Requests;

use Auth;
use Rule;
use Illuminate\Validation\Rules\Enum;
use App\Enums\EventCustomFieldTypeEnum;
use App\Enums\EventCustomFieldRuleEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class EventCustomFieldCreateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(EventCustomFieldTypeEnum::class)],
            'caption' => ['nullable'], // Make this caption required ???
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
            'status' => ['required', 'boolean'],
            'rule' => ['required', new Enum(EventCustomFieldRuleEnum::class)],
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
            'rule' => [
                'description' => "Whether the field is required or optional. Must be one of ".implode(', ', array_column(EventCustomFieldRuleEnum::cases(), 'value')),
                'example' => array_column(EventCustomFieldRuleEnum::cases(), 'value')[0]
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $data = [];

        if ($this->type != EventCustomFieldTypeEnum::Select->value) { // Ensure the possibilities field is always null when the value of the type field is not "select"
            $data['possibilities'] = null;
        }

        $this->merge($data);
    }
}
