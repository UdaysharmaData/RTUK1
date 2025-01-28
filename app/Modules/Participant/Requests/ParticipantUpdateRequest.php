<?php

namespace App\Modules\Participant\Requests;

use Str;
use Auth;
use Rule;
use App\Traits\SiteTrait;
use Illuminate\Support\Arr;
use App\Http\Helpers\AccountType;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\User;
use App\Modules\Event\Models\Event;
use App\Modules\Setting\Models\Site;
use Illuminate\Validation\Rules\Enum;
use App\Modules\Event\Models\EventPage;
use App\Modules\Charity\Models\Charity;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\IsEventEventCategoryFeeNotNull;
use App\Traits\FailedValidationResponseTrait;
use App\Modules\Event\Models\EventEventCategory;

use App\Enums\GenderEnum;
use App\Enums\FeeTypeEnum;
use App\Enums\RoleNameEnum;
use App\Enums\CharityUserTypeEnum;
use App\Enums\ParticipantWaiveEnum;
use App\Enums\ProfileEthnicityEnum;
use App\Enums\ParticipantStateEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Enums\ParticipantAddedViaEnum;
use App\Enums\EventCustomFieldRuleEnum;
use App\Enums\ParticipantPaymentStatusEnum;
use App\Enums\ParticipantProfileTshirtSizeEnum;
use App\Enums\ParticipantProfileWeeklyPhysicalActivityEnum;

class ParticipantUpdateRequest extends FormRequest
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
            $query->where('event_event_category.ref', $this->eec);
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
            'first_name' => ['sometimes', 'required', 'string'],
            'last_name' => ['sometimes', 'required', 'string'],
            'phone' => ['sometimes', 'nullable', 'phone:AUTO,GB'],

            // Validate user profile (contains the event registration/required fields)
            'state' => ['sometimes', 'nullable', new Enum(ParticipantStateEnum::class)],

            'profile.gender' => ['sometimes', 'nullable', new Enum(GenderEnum::class)],
            'profile.dob' => ['sometimes', 'nullable', 'date_format:d-m-Y'],
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

            // Validate participant profile (contains the event registration/required fields)
            'slogan' => ['sometimes', 'nullable', 'string'],
            'club' => ['sometimes', 'nullable', 'string'],
            'emergency_contact_name' => ['sometimes', 'nullable', 'string'],
            'emergency_contact_phone' => ['sometimes', 'nullable', 'phone:AUTO,GB'],
            'tshirt_size' => ['sometimes', 'nullable', new Enum(ParticipantProfileTshirtSizeEnum::class)],

            'user' => [
                'sometimes',
                'required',
                'string',
                Rule::exists('users', 'ref')->where(function ($query) { // User must exist and must have the Participant role.
                    $query->whereIn('users.id', function($query) {      // TODO: Create a route that enables an admin to assign roles to users so that account managers, event_managers etc can also have the participant role.
                        $query->select('role_user.user_id')
                            ->from('role_user')
                            ->where('users.ref', request()->user)
                            ->where('role_user.role_id', Role::where('name', RoleNameEnum::Participant)->first()->id);
                    });
                }),
            ],
            'eec' => [/*'sometimes', */'required', Rule::exists('event_event_category', 'ref')],
            'charity' => [
                Rule::exists('charities', 'ref'),/*->where(function ($query) {
                    $query->where('ref', request()->charity)
                        ->whereIn('charities.id', function($query) {
                            $query->select('charity_user.charity_id')
                                ->from('charity_user')
                                ->whereIn('charity_user.user_id', function($query) {
                                    $query->select('users.id')
                                        ->from('users')
                                        ->whereIn('users.id', function($query) {
                                            $query->select('role_user.user_id')
                                                ->from('role_user');
                    
                                            $query->where(function ($query) { // The Admin must have access permissions to the event
                                                $query->where('role_user.role_id', Role::where('name', RoleNameEnum::Administrator)->first()->id)
                                                    ->whereIn('users.id', function ($query) {
                                                        $query->select('site_user.user_id')
                                                            ->from('site_user')
                                                            ->where('site_user.site_id', Site::where('id', static::getSite()?->id)->first()?->id) // Ensure the admin has access to the site and the event is available on the site making the request
                                                            ->whereIn('site_user.site_id', Site::whereIn('id', function ($query) {
                                                                $query->select('event_categories.site_id')
                                                                    ->from('event_categories')
                                                                    ->whereIn('event_categories.id', function ($query) {
                                                                        $query->select('event_event_category.event_category_id')
                                                                        ->from('event_event_category')
                                                                        ->where('ref', request()->ecc_ref);
                                                                    });
                                                                })->pluck('id')->toArray()
                                                            );
                                                    });
                                            });
                    
                                            $query->orWhere(function ($query) { // The Charity, Charity User or Account Manager must have a relationship with the Charity
                                                $query->where(function ($query) {
                                                    $query->where('role_user.role_id', Role::where('name', RoleNameEnum::Charity)->first()->id)
                                                        ->orWhere('role_user.role_id', Role::where('name', RoleNameEnum::CharityUser)->first()->id)
                                                        ->orWhere('role_user.role_id', Role::where('name', RoleNameEnum::AccountManager)->first()->id);
                                                });
                
                                                $query->whereIn('users.id', function($query) {
                                                    $query->select('charity_user.user_id')
                                                        ->from('charity_user')
                                                        ->where('users.ref', request()->added_by)
                                                        ->where(function ($query) {
                                                            $query->where('charity_user.type', CharityUserTypeEnum::Owner)
                                                                ->orWhere('charity_user.type', CharityUserTypeEnum::User)
                                                                ->orWhere('charity_user.type', CharityUserTypeEnum::Manager);
                                                        })
                                                        ->where('charity_user.charity_id', Charity::where('ref', request()->charity)->first()?->id);
                                                });
                                            });
                                        });
                                });
                        });
                }) */
                Rule::requiredIf($this->waiver && $this->waiver == ParticipantWaiverEnum::Charity->value)
            ],
            // 'corporate' => ['sometimes', 'nullable', Rule::exists('corporates', 'ref')],
            'payment_status' => [/*'sometimes',*/ new Enum(ParticipantPaymentStatusEnum::class), function ($attribute, $value, $fail) {
                if (($value == ParticipantPaymentStatusEnum::Paid->value) && ! AccountType::isAdmin()) { // Ensure only the admin can set the paid payment status
                    $fail('Only the admin can set the payment status to paid');
                }
            },
                Rule::requiredIf($this->waive || $this->waiver || $this->fee_type),
            ],
            'waive' => [ // Required when the participant is exempted (partially or fully) from payment
                Rule::requiredIf($this->payment_status && $this->payment_status == ParticipantPaymentStatusEnum::Waived->value),
                Rule::prohibitedIf($this->payment_status && ($this->payment_status == ParticipantPaymentStatusEnum::Paid->value || $this->payment_status == ParticipantPaymentStatusEnum::Unpaid->value)),
                new Enum(ParticipantWaiveEnum::class)
            ],
            'waiver' => [ // Required when the participant is exempted (partially or fully) from payment
                Rule::requiredIf($this->payment_status && $this->payment_status == ParticipantPaymentStatusEnum::Waived->value),
                Rule::prohibitedIf($this->payment_status && ($this->payment_status == ParticipantPaymentStatusEnum::Paid->value || $this->payment_status == ParticipantPaymentStatusEnum::Unpaid->value)),
                new Enum(ParticipantWaiverEnum::class)
            ],
            'fee_type' => [
                Rule::requiredIf($this->payment_status && $this->payment_status == ParticipantPaymentStatusEnum::Paid->value),
                Rule::prohibitedIf($this->payment_status && $this->payment_status != ParticipantPaymentStatusEnum::Paid->value),
                new Enum(FeeTypeEnum::class),
                new IsEventEventCategoryFeeNotNull
            ],
            'preferred_heat_time' => ['sometimes', 'nullable', 'string'],
            'raced_before' => ['sometimes', 'required', 'boolean'],
            'speak_with_coach' => ['sometimes', 'required', 'boolean'],
            'hear_from_partner_charity' => ['sometimes', 'required', 'boolean'],
            'reason_for_participating' => ['sometimes', 'nullable', 'string'],
            'estimated_finish_time' => ['sometimes', 'nullable', 'string', /*'date_format:H:i:s'*/],
            'added_via' => ['sometimes', 'required', new Enum(ParticipantAddedViaEnum::class)],
            'event_page' => ['sometimes', 'nullable', 'string', Rule::exists('event_pages', 'ref')], // No need to make this field required if added_via == registration_page as the admin (only the admin can update this field) might not know or want to fill it.
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
            'eec.exists' => 'The selected event and category are invalid.',
            'eec.required' => 'The event and category are required.',
            'charity.required' => 'The charity field is required when waiver is charity.',
            'payment_status.required' => 'The payment status field is required when waive, waiver or fee_type is present.',
            'waive.required' => 'The waive field is required when payment status is waived.',
            'waiver.required' => 'The waiver field is required when payment status is waived.',
            'waive.prohibited' => 'The waive field is prohibited when payment status is not waived.',
            'waiver.prohibited' => 'The waiver field is prohibited when payment status is not waived.',
            'fee_type.required' => 'The fee type field is required when payment status is paid.',
            'fee_type.prohibited' => 'The fee type field is prohibited when payment status is not paid.',
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
            'gender' => [
                'description' => 'The gender. Must be one of '. implode(', ', array_column(GenderEnum::cases(), 'value')),
                'example' => GenderEnum::Male->value,
            ],
            'state' => [
                'description' => 'The participant state. Must be one of '. implode(', ', array_column(ParticipantStateEnum::cases(), 'value')),
                'example' => ParticipantStateEnum::PartiallyRegistered->value,
            ],
            'dob' => [
                'description' => 'The dob',
                'example' => '18-02-1980'
            ],
            'phone' => [
                'example' => '447834418119'
            ],
            'address' => [
                'example' => 'Chelsea Studios 410-412 Fulham Road., London SW6 1EB, UK'
            ],
            'city' => [
                'example' => 'London'
            ],
            'reg_state' => [
                'description' => 'The state they reside in',
                'example' => 'England'
            ],
            'postcode' => [
                'example' => 'SW6 1EB'
            ],
            'country' => [
                'example' => 'United Kingdom'
            ],
            'nationality' => [
                'example' => 'British'
            ],
            'occupation' => [
                'example' => 'Stock Broker',
            ],
            'passport_number' => [
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
            'charity' => [
                'description' => 'The ref of the charity. Must be one of '. Charity::where('name', 'WWF')->first()?->ref,
                'example' => Charity::where('name', 'WWF')->first()?->ref
            ],
            'eec' => [
                'description' => 'The ref of the event event category. Must be one of '.implode(', ', EventEventCategory::inRandomOrder()
                    ->whereHas('eventCategory', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        });
                    })->limit(3)->pluck('ref')->toArray()),
                'example' => EventEventCategory::inRandomOrder()
                    ->whereHas('eventCategory', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        });
                    })->first()?->ref
            ],
            'user' => [
                'description' => 'The refs of the user. Must be one of '.implode(', ', User::inRandomOrder()
                    ->whereHas('roles', function ($query) {
                        $query->where('name', RoleNameEnum::Participant);
                    })
                    ->whereHas('charityUser', function ($query) {
                        $query->where(function ($query) {
                            $query->where('type', CharityUserTypeEnum::Participant);
                        });
                        $query->whereHas('charity', function ($query) {
                            $query->where('name', 'WWF');
                        });
                    })
                    ->limit(3)->pluck('ref')->toArray()),
                'example' => User::inRandomOrder()
                    ->whereHas('roles', function ($query) {
                        $query->where('name', RoleNameEnum::Participant);
                    })
                    ->whereHas('charityUser', function ($query) {
                        $query->where(function ($query) {
                            $query->where('type', CharityUserTypeEnum::Participant);
                        });
                        $query->whereHas('charity', function ($query) {
                            $query->where('name', 'WWF');
                        });
                    })
                    ->first()?->ref
            ],
            'payment_status' => [
                'description' => "Must be one of ".implode(', ', array_column(ParticipantPaymentStatusEnum::cases(), 'value')),
                'example' => ParticipantPaymentStatusEnum::Unpaid->value
            ],
            'waive' => [
                'description' => "In case the participant is exempted (partially or fully) from payment. Is required and should be present when payment_status is ".ParticipantPaymentStatusEnum::Waived->value.". Must be one of ".implode(', ', array_column(ParticipantWaiveEnum::cases(), 'value')),
                'example' => ParticipantWaiveEnum::Completely->value
            ],
            'waiver' => [
                'description' => "The partner waiving or offering the place (In case the participant is exempted (partially or fully) from payment). Is required and should be present when payment_status is ".ParticipantPaymentStatusEnum::Waived->value.". Must be one of ".implode(', ', array_column(ParticipantWaiverEnum::cases(), 'value')),
                'example' => ParticipantWaiverEnum::Charity->value
            ],
            'fee_type' => [
                'description' => "The type of fee paid by the participant. Is required and should be present when payment_status is ".ParticipantPaymentStatusEnum::Paid->value.". Must be one of ".implode(', ', array_column(FeeTypeEnum::cases(), 'value')),
                'example' => FeeTypeEnum::Local->value
            ],
            'added_via' => [
                'description' => "Must be one of ".implode(', ', array_column(ParticipantAddedViaEnum::cases(), 'value')),
                'example' => ParticipantAddedViaEnum::PartnerEvents->value
            ],
            'weekly_physical_activity' => [
                'description' => "Must be one of ".implode(', ', array_column(ParticipantProfileWeeklyPhysicalActivityEnum::cases(), 'value')),
                'example' => ParticipantProfileWeeklyPhysicalActivityEnum::Days_1_2->value
            ],
            'event_page' => [
                'example' => Arr::random([
                    EventPage::inRandomOrder()
                        ->first()?->ref
                ])
            ],
            // 'charity_checkout_raised' => [
            //     'example' => Arr::random([null, null])
            // ],
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
