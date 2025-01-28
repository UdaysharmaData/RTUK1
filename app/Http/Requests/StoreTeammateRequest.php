<?php

namespace App\Http\Requests;

use App\Models\Teammate;
use App\Rules\EnsureUploadDataExists;
use Illuminate\Validation\Rules\File;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class StoreTeammateRequest extends FormRequest
{
    use FailedValidationResponseTrait;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'image' => [
                'required',
                'string',
                new EnsureUploadDataExists()
            ],
            ...Teammate::RULES['create_or_update']
        ];
    }
}
