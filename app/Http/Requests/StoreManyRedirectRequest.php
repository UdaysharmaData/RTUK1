<?php

namespace App\Http\Requests;

use App\Contracts\Redirectable;
use App\Enums\RedirectStatusEnum;
use App\Enums\RedirectTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreManyRedirectRequest extends FormRequest
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
            'redirects' => ['required', 'array'],
            'redirects.*.target_url' => ['required', 'url:https', Rule::unique('redirects', 'target_url')->where('site_id', clientSiteId())],
            'redirects.*.redirect_url' => ['required', 'url:https'],
            'redirects.*.status' => ['required', new Enum(RedirectStatusEnum::class)],
            'redirects.*.type' => ['required', new Enum(RedirectTypeEnum::class)],
            'redirects.*.model' => ['required', 'json', [
                function ($attribute, $value, $fail) {
                    $class = get_class(json_decode($value, true));

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
            ]],
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'redirects.*.status.Illuminate\Validation\Rules\Enum' => 'Invalid status specified',
            'redirects.*.type.Illuminate\Validation\Rules\Enum' => 'Invalid type specified',
            'redirects.*.target_url.unique' => 'A redirect already exists for this target url',
        ];
    }
}
