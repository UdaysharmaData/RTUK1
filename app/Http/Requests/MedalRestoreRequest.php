<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class MedalRestoreRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'refs' => ['required', 'array', 'exists:medals,ref'],
            'refs.*' => ['required']
        ];
    }

    public function bodyParameters()
    {
        return [
            'refs' => [
                'description' => 'An array of string refs of medals',
                'example' => ['97ad9df6-bc08-4729-b95e-3671dc6192c2', '97ad9df6-bc08-4729-b95e-3671dc6192c1']
            ]
        ];
    }
}
