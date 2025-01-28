<?php

namespace App\Http\Requests;

use App\Contracts\Redirectable;
use App\Enums\RedirectHardDeleteStatusEnum;
use App\Enums\RedirectSoftDeleteStatusEnum;
use App\Enums\RedirectTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreRedirectRequest extends FormRequest
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
            'target_url' => ['required', 'url:https', Rule::unique('redirects', 'target_url')->where('site_id', clientSiteId())],
            'redirect_url' => ['required', 'url:https'],
            'soft_delete' => ['required', new Enum(RedirectSoftDeleteStatusEnum::class)],
            'hard_delete' => ['required', new Enum(RedirectHardDeleteStatusEnum::class)],
            'type' => ['required', new Enum(RedirectTypeEnum::class)],
            'model' => ['sometimes', 'required', 'array'],
            'model.id' => ['sometimes', 'required', 'integer'],
            'models' => ['sometimes', 'required', 'array'],
            'models.*' => [
                function ($attribute, $value, $fail) {
                    $class = get_class($value);

                    if (! class_exists($class, true)) {
                        $fail($value . " is not an existing class");
                    }

//                    if (! in_array(Model::class, class_parents($class))) {
//                        $fail($value . " is not a valid entity");
//                    }

                    if (! in_array(Redirectable::class, class_implements($class))) {
                        $fail($value . " does not support redirects");
                    }
                }
            ]
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
