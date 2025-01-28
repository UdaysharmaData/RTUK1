<?php

namespace App\Http\Requests;

use Auth;
use Rule;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class InvoiceUpdateRequest extends FormRequest
{
    use FailedValidationResponseTrait;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'description' => ['nullable', 'string'],
            'issue_date' => ['required', 'date_format:d-m-Y', 'after:today'],
            'due_date' => ['required', 'date_format:d-m-Y', 'after_or_equal:issue_date'],
            // 'price' => ['required', 'numeric', 'between:0,999999.99'],
            'held' => ['boolean'],
            'send_on' => [
                'nullable',
                'date_format:d-m-Y',
                'after:today',
                Rule::requiredIf($this->held && !empty($this->held) ? true : false)
            ]
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'held.required' => 'The held attribute is required when send on is not empty',
            'send_on.required' => 'The send on attribute is required when held is true'
        ];
    }

    public function bodyParameters()
    {
        return [
            'description' => [
                'description' => 'The description',
                'example' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'
            ],
            'issue_date' => [
                'description' => 'The date the invoice was created/issued.',
                'example' => Carbon::now()->addDay()->format('d-m-Y')
            ],
            'due_date' => [
                'description' => 'The due date of the invoice.',
                'example' => Carbon::now()->addDay()->addWeeks(2)->format('d-m-Y')
            ],
            // 'price' => [
            //     'description' => 'The invoice cost.',
            //     'example' => 98879.97
            // ],
            'send_on' => [
                'description' => 'The date at which the invoice should be sent.',
                'example' => Carbon::now()->addWeek()->format('d-m-Y')
            ],
        ];
    }

 
}
