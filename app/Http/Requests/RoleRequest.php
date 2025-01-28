<?php

namespace App\Http\Requests;

use Str;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class RoleRequest extends FormRequest
{
    use FailedValidationResponseTrait;

    /*
    | Used to validate roles create and update requests
    */

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // return Auth::check();
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
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,'.($this->_name ? $this->_name.',name' : '')],
            'display_name' => ['required', 'string', 'max:255'],
            'permissions' => ['required', 'array', 'exists:permissions,id'],
            'permissions.*' => ['integer'],
            'description' => ['required', 'string']
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            // TODO: Prevent scribe from generating this name propery among the request body params
            // The value of this name parameter is generated from the display_name parameter(internally) before validation. Please ignore it when filling the form for now
            // while we figure out how to prevent scribe from adding this name parameter to the request body parameters
            'name' => Str::lower(Str::slug($this->display_name, '_'))
        ]);
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'name.unique' => 'A role with that name already exists!'
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The value of this name parameter is generated from the display_name parameter(internally) before validation. Please ignore it when filling the form for now
                    while we figure out how to prevent scribe from adding this name parameter to the request body parameters',
                'example' => NUll
            ],
            'display_name' => [
                'description' => 'The role display name.',
                'example' => 'Account Managers'
            ],
            'permissions' => [
                'display_name' => 'The role\'s permissions.',
                'example' => '[1,3,5]'
            ],
            'description' => [
                'example' => 'Manages charity accounts on the application.'
            ],
        ];
    }

 
}
