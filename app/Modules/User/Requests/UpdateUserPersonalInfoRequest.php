<?php

namespace App\Modules\User\Requests;

use App\Enums\GenderEnum;
use App\Enums\ParticipantProfileTshirtSizeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateUserPersonalInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return request()->user()->id === $this->route('user')?->id
            || request()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($this->route('user')?->id),
            ],
            'phone' => ['required', 'string', 'max:14'],
            'gender' => ['sometimes', 'nullable', 'string', new Enum(GenderEnum::class)],
            'dob' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d', 'before:today'],
            'country' => ['sometimes', 'nullable', 'string'],
            'state' => ['sometimes', 'nullable', 'string'],
            'city' => ['sometimes', 'nullable', 'string'],
            'postcode' => ['sometimes', 'nullable', 'string'],
            'address' => ['sometimes', 'nullable', 'string'],
            'nationality' => ['sometimes', 'nullable', 'string'],
            'occupation' => ['sometimes', 'nullable', 'string'],
            'passport_number' => ['sometimes', 'nullable', 'string'],
            'bio' => ['sometimes', 'nullable', 'string'],
            'tshirt_size' => ['sometimes', 'nullable', 'string', new Enum(ParticipantProfileTshirtSizeEnum::class)],
            'emergency_contact_name' => ['sometimes', 'nullable', 'string'],
            'emergency_contact_phone' => ['sometimes', 'nullable', 'string'],
            'slogan' => ['sometimes', 'nullable', 'string'],
            'club' => ['sometimes', 'nullable', 'string']
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'gender.Illuminate\Validation\Rules\Enum' => 'Invalid gender specified',
            'tshirt_size.Illuminate\Validation\Rules\Enum' => 'Invalid t-shirt size selected'
        ];
    }
}
