<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UpdateActiveRoleRequest extends FormRequest
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
            'role' => [
                'required',
                'string',
//                'exists:roles,name',
                $this->isAssigned()
            ],
        ];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            //
        ];
    }

    /**
     * @return \Closure
     */
    private function isAssigned(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if (! $this->route('user')?->hasRole($value)) $fail('You are not currently assigned this role.');
        };
    }
}
