<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RevokePermissionRequest extends FormRequest
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
            'permission_name' => ['required', 'string', 'exists:permissions,name'],
        ];
    }
}
