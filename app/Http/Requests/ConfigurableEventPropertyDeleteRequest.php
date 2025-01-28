<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use Illuminate\Validation\Rule;

class ConfigurableEventPropertyDeleteRequest extends FormRequest
{
    use FailedValidationResponseTrait;

    /**
     * @var string
     */
    protected string $label;

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
     * @return array
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => [
                'numeric',
                Rule::exists($this->label, 'id')->where(
                    function ($query) {
                        return $query->where('site_id', clientSiteId());
                    }
                )
            ]
        ];
    }
}
