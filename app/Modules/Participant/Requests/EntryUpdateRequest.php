<?php

namespace App\Modules\Participant\Requests;

use Str;
use Auth;
use Rule;
use App\Enums\GenderEnum;
use App\Traits\SiteTrait;
use Illuminate\Support\Arr;
use App\Modules\Event\Models\Event;
use App\Enums\ProfileEthnicityEnum;
use Illuminate\Validation\Rules\Enum;
use App\Enums\EventCustomFieldRuleEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use App\Enums\ParticipantProfileTshirtSizeEnum;
use App\Enums\ParticipantProfileWeeklyPhysicalActivityEnum;

class EntryUpdateRequest extends FormRequest
{
    use FailedValidationResponseTrait, SiteTrait;

    private $eventCustomFields; // The custom fields of the event

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
        $customFields = []; // The event custom fields

        $this->eventCustomFields = Event::whereHas('eventCategories', function ($query) {
            $query->where('event_event_category.ref', $this->participant?->eventEventCategory?->ref);
        })->first()?->eventCustomFields;

        if (! empty($this->eventCustomFields)) { // Set validation rules to the event custom fields
            foreach ($this->eventCustomFields as $customField) {
                $customFields = [
                    ...$customFields,
                    "custom_fields.".$customField->slug => ['sometimes', ($customField->rule == EventCustomFieldRuleEnum::Optional ? 'nullable' : $customField->rule->value), 'string'] // TODO: Check the custom field type and set the validation rule according to it. Ensure the value submitted for the select input type matches one of it's options provided. Do well to also update the messages to reflect these changes
                ];
            }
        }

        return [
            // Validate user
            // 'email' => ['required', 'string', 'unique:users,email'],
            'first_name' => ['sometimes', 'required', 'string'],
            'last_name' => ['sometimes', 'required', 'string'],
            'phone' => ['sometimes', 'nullable', 'phone:AUTO,GB'],

            // Validate user profile
            'profile.gender' => ['sometimes', 'nullable', new Enum(GenderEnum::class)],
            'dob' => ['sometimes', 'nullable', 'date_format:d-m-Y'],
            'profile.city' => ['sometimes', 'nullable', 'string'],
            'profile.state' => ['sometimes', 'nullable', 'string'],
            'profile.address' => ['sometimes', 'required', 'string'],
            'profile.country' => ['sometimes', 'nullable', 'string'],
            'profile.postcode' => ['sometimes', 'nullable', 'string'],
            'profile.nationality' => ['sometimes', 'nullable', 'string'],
            'profile.occupation' => ['sometimes', 'nullable', 'string'],
            'profile.passport_number' => ['sometimes', 'nullable', 'string'],
            'profile.ethnicity' => ['sometimes', 'nullable', new Enum(ProfileEthnicityEnum::class)],
            'weekly_physical_activity' => ['sometimes', 'nullable', new Enum(ParticipantProfileWeeklyPhysicalActivityEnum::class)],

            // Validate participant profile
            'slogan' => ['sometimes', 'nullable', 'string'],
            'club' => ['sometimes', 'nullable', 'string'],
            'emergency_contact_name' => ['sometimes', 'nullable', 'string'],
            'emergency_contact_phone' => ['sometimes', 'nullable', 'phone:AUTO,GB'],
            'tshirt_size' => ['sometimes', 'nullable', new Enum(ParticipantProfileTshirtSizeEnum::class)],

            // 'corporate' => ['sometimes', 'nullable', Rule::exists('corporates', 'ref')],
            'preferred_heat_time' => ['sometimes', 'nullable', 'string'],
            'raced_before' => ['sometimes', 'required', 'boolean'],
            'speak_with_coach' => ['sometimes', 'required', 'boolean'],
            'hear_from_partner_charity' => ['sometimes', 'required', 'boolean'],
            'reason_for_participating' => ['sometimes', 'nullable', 'string'],
            'estimated_finish_time' => ['sometimes', 'nullable', 'string', /*'date_format:H:i:s'*/],
            // 'charity_checkout_raised' => ['sometimes', 'nullable', 'numeric', 'between:0,999999.99'],
            // 'charity_checkout_title' => ['sometimes', 'nullable', 'string'],
            // 'charity_checkout_status' => ['sometimes', 'required', 'boolean'],
            // 'charity_checkout_created_at' => ['sometimes', 'nullable', 'date'],
            // 'how_much_raised' => ['sometimes', 'nullable', 'numeric', 'between:0,999999.99'],
            'enable_family_registration' => ['sometimes', 'required', 'boolean'],

            // Validate event custom fields
            'custom_fields' => ['sometimes'],
            ...$customFields
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            ...$this->customFieldsMessages()
        ];
    }

    public function bodyParameters()
    {
        return [
            'first_name' => [
                'example' => 'Marc'
            ],
            'last_name' => [
                'example' => 'Roby AM'
            ],
            'phone' => [
                'example' => '447834418119'
            ],
            'profile.gender' => [
                'description' => 'The gender. Must be one of '. implode(', ', array_column(GenderEnum::cases(), 'value')),
                'example' => GenderEnum::Male->value,
            ],
            'profile.dob' => [
                'description' => 'The dob',
                'example' => '18-02-1980'
            ],
            'profile.address' => [
                'example' => 'Chelsea Studios 410-412 Fulham Road., London SW6 1EB, UK'
            ],
            'profile.city' => [
                'example' => 'London'
            ],
            'profile.state' => [
                'description' => 'The state they reside in',
                'example' => 'England'
            ],
            'profile.postcode' => [
                'example' => 'SW6 1EB'
            ],
            'profile.country' => [
                'example' => 'United Kingdom'
            ],
            'profile.nationality' => [
                'example' => 'British'
            ],
            'profile.occupation' => [
                'example' => 'Stock Broker',
            ],
            'profile.passport_number' => [
                'example' => 'P5508000A',
            ],
            'emergency_contact_name' => [
                'example' => 'John Doe'
            ],
            'emergency_contact_phone' => [
                'example' => '07851081623'
            ],
            'tshirt_size' => [
                'description' => "Must be one of ".implode(', ', array_column(ParticipantProfileTshirtSizeEnum::cases(), 'value')),
                'example' => ParticipantProfileTshirtSizeEnum::XL->value
            ],
            'charity_checkout_raised' => [
                'example' => Arr::random([null, null])
            ],
            'weekly_physical_activity' => [
                'description' => "Must be one of ".implode(', ', array_column(ParticipantProfileWeeklyPhysicalActivityEnum::cases(), 'value')),
                'example' => ParticipantProfileWeeklyPhysicalActivityEnum::Days_1_2->value
            ]
        ];
    }

    /**
     * Dynamically get response messages for custom fields
     *
     * @return array
     */
    protected function customFieldsMessages(): array
    {
        $messages = [];

        if (! empty($this->eventCustomFields)) {
            foreach ($this->eventCustomFields as $customField) {
                $messages = [
                    ...$messages,
                    'custom_fields.'.$customField->slug.'.required' => "The ".Str::lower($customField->name)." field is required.",
                    'custom_fields.'.$customField->slug.'.string' => "The ".Str::lower($customField->name)." field must be a string."
                ];
            }
        }

        return $messages;
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $data = [];

        if (isset($this->custom_fields)) { // Cast custom fields values (bool, integer etc) to string
            foreach ($this->custom_fields as $key => $value) {
                $data['custom_fields'][$key] = (is_bool($value) && !$value) ? "0" : (string) $value;
            }
        }

        $this->merge($data);
    }
}
