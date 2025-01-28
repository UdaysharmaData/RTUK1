<?php

namespace App\Modules\User\Requests;

use App\Services\TwoFactorAuth\Rules\VerifyTwoFactorCode;
use Illuminate\Foundation\Http\FormRequest;

class TwoFactorCodeRequest extends FormRequest
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
          'totp_code' => ['required', new VerifyTwoFactorCode()]
        ];
    }
}
