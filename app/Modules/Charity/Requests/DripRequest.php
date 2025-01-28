<?php

namespace App\Modules\Charity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class DripRequest extends FormRequest
{
    use FailedValidationResponseTrait;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // return Auth::check();
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:128'],
            'subject' => ['required', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
            'schedule_type' => ['required', 'in:before,after'],
            'schedule_days' => ['required', 'integer'],
            'template' => ['required', 'in:welcome-email,intermediate-1-email,intermediate-2-email,thank-you-email']
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'example' => 'Thank You Email'
            ],
            'subject' => [
                'example' => 'Thank You for Your Support'
            ],
            'status' => [
                'example' => 0
            ],
            'schedule_type' => [
                'example' => 'before'
            ],
            'schedule_days' => [
                'example' => 15
            ],
            'template' => [
                'example' => 'welcome-email'
            ],
        ];
    }
}
