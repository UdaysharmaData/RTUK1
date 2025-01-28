<?php

namespace App\Http\Requests;

use App\Models\MediaLibraryCollection;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class UpdateMediaLibraryCollectionRequest extends FormRequest
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
            ...MediaLibraryCollection::RULES['create_or_update']
        ];
    }
}
