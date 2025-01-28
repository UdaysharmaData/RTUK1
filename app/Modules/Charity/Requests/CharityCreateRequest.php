<?php

namespace App\Modules\Charity\Requests;

use Auth;
use Rule;
use App\Enums\RoleNameEnum;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

use App\Modules\Charity\Requests\CharityUpdateContentRequest;

class CharityCreateRequest extends FormRequest
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
        $rules = [
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'name' => ['required', 'string', 'unique:charities,name'],
            'email' => ['required', 'email', 'unique:users'],
            'account_manager' => [
                'sometimes',
                'required',
                'string',
                Rule::exists('users', 'ref')->where(function ($query) { // User must exist and must have the Account Manager role
                    $query->whereIn('users.id', function($query) {
                        $query->select('role_user.user_id')
                            ->from('role_user')
                            ->where('users.ref', request()->account_manager)
                            ->where('role_user.role_id', Role::where('name', RoleNameEnum::AccountManager)->first()->id);
                    });
                }),
            ],
            'category' => ['required', 'string', 'exists:charity_categories,slug'],
            'support_email' => ['required', 'email'],
            'address' => ['required', 'string', 'max:150'],
            'postcode' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string'],
            'country' => ['required', 'string'],
            'phone' => ['required', 'phone:AUTO,GB'],
            'primary_color' => ['sometimes', 'required', 'string'],
            'secondary_color' => ['sometimes', 'required', 'string'],
            'logo' => ['sometimes', 'required', 'base64image', 'base64mimes:jpeg,png,jpg,gif,svg,webp,avif', 'base64max:10240']
        ];

        $rules = array_merge($rules, (new CharityUpdateContentRequest)->rules());

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'name.unique' => 'A charity with that name already exists.'
        ];
    }

    public function bodyParameters()
    {
        return [
            'first_name' => [
                'description' => 'The charity owner first name',
                'example' => 'Marc',
            ],
            'last_name' => [
                'description' => 'The charity owner last name',
                'example' => 'Roby AM',
            ],
            'name' => [
                'description' => 'The charity name',
                'example' => 'Say no to cancer',
            ],
            'email' => [
                'description' => 'The charity owner email address',
            ],
            'account_manager' => [
                'description' => 'The charity\'s account manager ref',
                'example' => User::inRandomOrder()
                    ->whereHas('roles', function ($query) {
                        $query->where('name', RoleNameEnum::AccountManager);
                    })->first()->ref
            ],
            'category' => [
                'description' => 'The charity category (slug)',
                'example' => 'cancer-children-youth'
            ],
            'support_email' => [
                'description' => 'The charity email address (support email)',
            ],
            'address' => [
                'description' => 'The charity address',
                'example' => 'Fourth Floor, Maya House, 134-138 Borough High St'
            ],
            'city' => [
                'example' => 'Westminster'
            ],
            'country' => [
                'example' => 'England'
            ],
            'postcode' => [
                'example' => 'SE1 1LB'
            ],
            'phone' => [
                'example' => '+447815176034'
            ],
            'primary_color' => [
                'example' => '#3DFF1F'
            ],
            'secondary_color' => [
                'example' => '#FF4A1C'
            ]
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
            'name' => ucfirst(trim($this->name)),
            'email' => trim($this->email),
            'support_email' => trim($this->support_email)
        ]);
    }
}
