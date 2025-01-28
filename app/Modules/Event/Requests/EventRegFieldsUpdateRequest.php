<?php

namespace App\Modules\Event\Requests;

use Auth;
use Rule;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class EventRegFieldsUpdateRequest extends FormRequest
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
            'reg_first_name' => ['sometimes', 'required', 'boolean'],
            'reg_last_name' => ['sometimes', 'required', 'boolean'],
            'reg_email' => ['sometimes', 'required', 'boolean'],
            'reg_gender' => ['sometimes', 'required', 'boolean'],
            'reg_dob' => ['sometimes', 'required', 'boolean'],
            'reg_phone' => ['sometimes', 'required', 'boolean'],
            'reg_preferred_heat_time' => ['sometimes', 'required', 'boolean'],
            'custom_preferred_heat_time' => ['sometimes'],
            'custom_preferred_heat_time.custom_preferred_heat_time_start' => [
                'string',
                Rule::prohibitedIf(!$this->reg_preferred_heat_time),
                Rule::requiredIf($this->reg_preferred_heat_time ? true : false)
            ],
            'custom_preferred_heat_time.custom_preferred_heat_time_end' => [
                'string',
                Rule::prohibitedIf(!$this->reg_preferred_heat_time),
                Rule::requiredIf($this->reg_preferred_heat_time ? true : false)
            ],
            'reg_raced_before' => ['sometimes', 'required', 'boolean'],
            'reg_estimated_finish_time' => ['sometimes', 'required', 'boolean'],
            'reg_tshirt_size' => ['sometimes', 'required', 'boolean'],
            'reg_age_on_race_day' => ['sometimes', 'required', 'boolean'],
            'reg_month_born_in' => ['sometimes', 'required', 'boolean'],
            'reg_nationality' => ['sometimes', 'required', 'boolean'],
            'reg_occupation' => ['sometimes', 'required', 'boolean'],
            'reg_address' => ['sometimes', 'required', 'boolean'],
            'reg_city' => ['sometimes', 'required', 'boolean'],
            'reg_state' => ['sometimes', 'required', 'boolean'],
            'reg_postcode' => ['sometimes', 'required', 'boolean'],
            'reg_country' => ['sometimes', 'required', 'boolean'],
            'reg_emergency_contact_name' => ['sometimes', 'required', 'boolean'],
            'reg_emergency_contact_phone' => ['sometimes', 'required', 'boolean'],
            'reg_passport_number' => ['sometimes', 'required', 'boolean'],
            'reg_family_registrations' => ['sometimes', 'required', 'boolean'],
            'reg_minimum_age' => ['sometimes', 'required', 'numeric', 'integer'],
            'born_before' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'reg_ethnicity' => ['sometimes', 'required', 'boolean'],
            'reg_weekly_physical_activity' => ['sometimes', 'required', 'boolean'],
            'reg_speak_with_coach' => ['sometimes', 'required', 'boolean'],
            'reg_hear_from_partner_charity' => ['sometimes', 'required', 'boolean'],
            'reg_reason_for_participating' => ['sometimes', 'required', 'boolean']
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'custom_preferred_heat_time.custom_preferred_heat_time_start.required' => 'The custom preferred heat time start field is required when prefered heat time is true.',
            'custom_preferred_heat_time.custom_preferred_heat_time_end.required' => 'The custom preferred heat time end field is required when prefered heat time is true.',
            'custom_preferred_heat_time.custom_preferred_heat_time_start.prohibited' => 'The custom preferred heat time start field is prohibed when prefered heat time is false.',
            'custom_preferred_heat_time.custom_preferred_heat_time_end.prohibited' => 'The custom preferred heat time end field is prohibed when prefered heat time is false.',
        ];
    }

    public function bodyParameters()
    {
        return [
            'custom_preferred_heat_time.custom_preferred_heat_time_start' => [
                'example' => '08:30 - 09:00',
            ],
            'custom_preferred_heat_time.custom_preferred_heat_time_end' => [
                'example' => '09:30 - 10:00',
            ],
            'born_before' => [
                'description' => 'The participant must be born before this year to be able to register.',
                'example' => Carbon::now()->subYears(random_int(5, 14))->toDateString(),
            ]
        ];
    }
}
