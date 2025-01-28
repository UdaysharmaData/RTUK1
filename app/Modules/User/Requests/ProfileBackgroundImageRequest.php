<?php

namespace App\Modules\User\Requests;

use App\Rules\DataUriImageSize;
use Illuminate\Foundation\Http\FormRequest;
use Intervention\Validation\Rules\DataUri;

class ProfileBackgroundImageRequest extends FormRequest
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
            'background_image' => [
                'required',
                new DataUri(),
                new DataUriImageSize()
            ],
        ];
    }
}
