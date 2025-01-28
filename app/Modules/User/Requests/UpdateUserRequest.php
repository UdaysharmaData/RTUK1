<?php

namespace App\Modules\User\Requests;

use App\Enums\GenderEnum;
use App\Modules\User\Models\Profile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\File;
use Intervention\Validation\Rules\Base64;
use Intervention\Validation\Rules\DataUri;

class UpdateUserRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($this->route('user')?->id),
            ],
            'phone' => ['required', 'string'],
            'gender' => ['nullable', 'string', new Enum(GenderEnum::class)],
            'dob' => ['nullable', 'date', 'date_format:Y-m-d', 'before:today'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
//            'avatar' => [
//                'sometimes',
//                'required',
//                new DataUri()
////                File::image()
////                    ->max(1024)
////                    ->dimensions(Rule::dimensions()->maxWidth(1000)->maxHeight(500)),
//            ],
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'gender.Illuminate\Validation\Rules\Enum' => 'Invalid gender specified'
        ];
    }
}
