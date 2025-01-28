<?php

namespace App\Http\Requests;

use Auth;
use Rule;
use App\Enums\GenderEnum;
use App\Enums\RoleNameEnum;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Role;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use App\Modules\Charity\Requests\CharityUpdateContentRequest;

class FamilyRegistrationRequest extends FormRequest
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
            'participant_id' => ['required', 'integer', Rule::exists('participants', 'id')],
            'event_custom_field_id' => ['required', 'integer', Rule::exists('event_custom_fields', 'id')],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'gender' => ['required', new Enum(GenderEnum::class)],
            // 'dob' => []
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            // 'dob.ecf_family_registrations_dob' => 'The date of birth field does not match the age group.'

        ];
    }

    public function bodyParameters()
    {
        return [
            'first_name' => [
                'example' => 'Marc'
            ],
            'last_name' => [
                'example' => 'Roby AM'
            ],
            'gender' => [
                'example' => GenderEnum::Male->value
            ],
            'dob' => [
                'example' => '12-10-2003'
            ]
        ];
    }


}
