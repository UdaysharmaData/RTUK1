<?php

namespace App\Http\Requests;

use App\Models\ApiClient;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use Illuminate\Validation\Rule;

class UpdateApiClientRequest extends FormRequest
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
            'update' => [
                'name' => ['sometimes', 'string', Rule::unique('api_clients')->ignore($this->route('api_client'))?->id],
                'host' => ['sometimes', 'string', Rule::unique('api_clients')->ignore($this->route('api_client'))?->id],
                'ip' => ['sometimes', 'nullable', 'ip', Rule::unique('api_clients')->ignore($this->route('api_client'))?->id],
                'is_active' => ['sometimes', 'required', 'boolean'],
                'site_id' => ['sometimes', 'integer', 'exists:sites,id'],
            ]
        ];
    }
}
