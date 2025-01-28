<?php

namespace App\Http\Requests;

use App\Models\Experience;
use App\Traits\DraftCustomValidator;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class StoreExperienceRequest extends FormRequest
{
    use FailedValidationResponseTrait, DraftCustomValidator;

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
            'name' => ['required', 'string', 'max:100', 'unique:experiences'],
            ...Experience::RULES['create_or_update'],
            ...$this->draftRules()
        ];
    }
}
