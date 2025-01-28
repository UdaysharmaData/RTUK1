<?php

namespace App\Modules\User\Requests;

use App\Enums\GenderEnum;
use App\Rules\UniqueToSite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Intervention\Validation\Rules\DataUri;

class StoreUserRequest extends FormRequest
{
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
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z]+$/'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z]+$/'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                new UniqueToSite,
            ],
            'phone' => ['required', 'string', 'max:14'],
            'gender' => ['nullable', 'string', new Enum(GenderEnum::class)],
            'dob' => ['sometimes', 'date', 'date_format:Y-m-d', 'before:today'],
//            'password' => ['required', Password::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
            'avatar' => [
                'sometimes',
                'required',
                new DataUri()
            ],
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'gender.Illuminate\Validation\Rules\Enum' => 'Invalid gender specified',
        ];
    }
}
