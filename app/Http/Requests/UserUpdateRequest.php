<?php

namespace App\Http\Requests;

use Rule;
use App\Http\Helpers\AccountType;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class UserUpdateRequest extends FormRequest
{
    use FailedValidationResponseTrait;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
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
            'email' => 'required|email|unique:users,email,'.$this->id,
            'first_name' => 'required|max:60',
            'last_name' => 'nullable|max:60',
            'company' => 'nullable|max:60',
            'role' => 'required|string|exists:roles,name',
            'default_site' => 'required|string|exists:sites,domain',
            'phone' => ['sometimes', 'nullable', 'phone:AUTO,GB'],
            'charity_id' => [
                'required_if:role,charity_user',
                'integer',
                Rule::exists('charities', 'id')->where(function ($query) {
                    if (AccountType::isManager()) {
                        $query->where('manager_id', Auth::user()->id);
                    }
                    if (AccountType::isCharity()) {
                        $query->where('user_id', Auth::user()->id);
                    }
                }),
            ],
            'password' => [
                'sometimes',
                'required',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ]
        ];
    }

    public function bodyParameters()
    {
        return [
            'role' => [
                'description' => 'The role of the user',
                'example' => 'participant',
            ],
            'default_site' => [
                'description' => 'The site making the request',
                'example' => 'runforcharity.com',
            ],
            'password' => [
                'description' => 'The user password. The password should have a minimum of 8 characters and should contain a combination of mixedCase, letters, numbers, and symbols.',
                'example' => 'Pass*149',
            ]
        ];
    }

 
}
