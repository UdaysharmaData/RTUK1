<?php

namespace App\Http\Requests;

use App\Models\Article;
use App\Rules\EnsureUploadDataExists;
use Intervention\Validation\Rules\DataUri;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class StoreArticleRequest extends FormRequest
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
            ...Article::RULES['create_or_update'],
            'tags' => ['required', 'array', 'max:5'],
            'tags.*' => ['string'],
            'cover_image' => [
                'required',
                'string',
                new EnsureUploadDataExists()
            ],
        ];
    }
}
