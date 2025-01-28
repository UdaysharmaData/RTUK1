<?php

namespace App\Http\Requests;

use Auth;
use Rule;
use App\Http\Helpers\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class UserCreateRequest extends FormRequest
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
            'email' => ['required', 'email', 'unique:users,email'],
            'first_name' => ['required', 'string'],
            'last_name' => ['nullable', 'string'],
            'company' => ['nullable', 'string'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'default_site' => ['required', 'string', 'exists:sites,domain'],
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
            'charity_id' => [
                'description' => 'The id of the charity. Only fill this parameter when creating a Charity User. The parameter is only required when it is set.'
            ]
        ];
    }

 
}
