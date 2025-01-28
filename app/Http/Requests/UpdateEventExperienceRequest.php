<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class UpdateEventExperienceRequest extends FormRequest
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
            'experiences' => ['required', 'array', 'min:1'],
            'experiences.*' => ['required', 'integer', $this->isValidExperienceId()],
        ];
    }

    /**
     * @return \Closure
     */
    private function isValidExperienceId(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $match = (bool) $this->route('event')?->experiences()
                ->where('experiences.id', $value)
                ->exists();

            if (! $match) $fail("You have specified an invalid experience $value for the specified event.");
        };
    }
}
