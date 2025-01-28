<?php

namespace App\Http\Requests;

use App\Models\Experience;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class StoreEventExperienceRequest extends FormRequest
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
            'experience' => ['required', 'string', 'exists:experiences,name'],
            'value' => ['required', 'string', $this->isValidExperienceValue()],
            'description' => ['required', 'string']
        ];
    }

    /**
     * @return \Closure
     */
    private function isValidExperienceValue(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $match = Experience::where('name', $this->request->get('experience'))
                ->first()?->values;

            if (! in_array($value, $match ?? [])) $fail('Specified experience does not have this value.');
        };
    }
}
