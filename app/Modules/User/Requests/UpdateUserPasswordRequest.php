<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UpdateUserPasswordRequest extends FormRequest
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
            'old_password' => ['required', 'string', $this->isVerifiedPassword()],
            'new_password' => ['required', Password::defaults()],
        ];
    }

    /**
     * @return \Closure
     */
    private function isVerifiedPassword(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if (! Hash::check($value, $this->route('user')?->password)) $fail('Invalid password.');
        };
    }
}
