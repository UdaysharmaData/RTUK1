<?php

namespace App\Services\PasswordProtectionPolicy\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePassphraseRequest extends FormRequest
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
     * @return array
     */
    public function rules(): array
    {
        $minLength = config('passwordprotectionpolicy.min_passphrase_length');

        return [
            'question' => ['nullable', 'string'],
            'response' => ['required', 'string', "min:$minLength"],
        ];
    }
}
