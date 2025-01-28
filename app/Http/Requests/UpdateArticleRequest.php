<?php

namespace App\Http\Requests;

use App\Models\Article;
use App\Rules\EnsureUploadDataExists;
use Intervention\Validation\Rules\DataUri;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class UpdateArticleRequest extends FormRequest
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
            'tags' => ['required', 'array'],
            'tags.*.name' => ['required', 'string'],
            'cover_image' => ['sometimes', 'required', 'string', new EnsureUploadDataExists()],
        ];
    }
}
