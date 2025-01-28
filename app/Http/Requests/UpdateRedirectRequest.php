<?php

namespace App\Http\Requests;

use App\Enums\RedirectHardDeleteStatusEnum;
use App\Enums\RedirectSoftDeleteStatusEnum;
use App\Enums\RedirectTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateRedirectRequest extends FormRequest
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
            'target_url' => ['sometimes', 'required', 'url:https',
                Rule::unique('redirects', 'target_url')
                    ->where('site_id', clientSiteId())
                    ->ignore($this->route('redirect'))
            ],
            'redirect_url' => ['sometimes', 'required', 'url:https'],
            'soft_delete' => ['sometimes', 'required', new Enum(RedirectSoftDeleteStatusEnum::class)],
            'hard_delete' => ['sometimes', 'required', new Enum(RedirectHardDeleteStatusEnum::class)],
            'type' => ['sometimes', 'required', new Enum(RedirectTypeEnum::class)],
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'soft_delete.Illuminate\Validation\Rules\Enum' => 'Invalid soft delete status specified',
            'hard_delete.Illuminate\Validation\Rules\Enum' => 'Invalid hard delete status specified',
            'type.Illuminate\Validation\Rules\Enum' => 'Invalid type specified',
            'target_url.unique' => 'A redirect already exists for this target url',
        ];
    }
}
