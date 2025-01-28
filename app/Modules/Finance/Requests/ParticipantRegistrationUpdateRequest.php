<?php

namespace App\Modules\Finance\Requests;

use Auth;
use Rule;
use App\Modules\Setting\Enums\SiteEnum;
use App\Enums\GenderEnum;
use App\Traits\SiteTrait;
use App\Rules\IsActiveEvent;
use App\Modules\Setting\Enums\OrganisationEnum;
use App\Http\Helpers\AccountType;
use App\Rules\IsRegisteredToEEC_2;
use Illuminate\Validation\Rules\Enum;
use App\Enums\ParticipantAddedViaEnum;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use App\Modules\Event\Models\EventEventCategory;

class ParticipantRegistrationUpdateRequest extends FormRequest
{
    use FailedValidationResponseTrait,
        SiteTrait;

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
     * @return array
     */
    public function rules()
    {
        $eec = [ // Collect data and validate these fields for unauthenticated users
            // Validate event
            'eec' => ['required'],
            'eec.*.ref' => [
                'bail',
                'required',
                'string',
                Rule::exists('event_event_category', 'ref')->where(function ($query) { // Ensure the site has access to the event having the eec
                    if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                        $query->whereIn('event_event_category.event_category_id', function($query) {
                            $query->select('event_categories.id')
                                ->from('event_categories')
                                ->whereIn('event_categories.site_id', function ($query) {
                                    $query->select('sites.id')
                                        ->from('sites')
                                        ->where('sites.id', static::getSite()?->id);
                                });
                        });
                    }
                }),
                new IsActiveEvent,
                new IsRegisteredToEEC_2
            ],
            // 'eec.*.quantity' => [
            //     'required',
            //     'integer',
            //     'min:1',
            //     // 'numeric',
            // ]
            'added_via' => ['required', new Enum(ParticipantAddedViaEnum::class)],
            'save_payment_method' => ['sometimes', 'required', 'boolean'],
            'default_payment_method' => ['sometimes', 'required', 'boolean']
        ];

        $user = [   // Validate user
            'user' => ['required'],
            'user.email' => ['required', 'string', 'email'],
            'user.first_name' => ['required', 'string'],
            'user.last_name' => ['required', 'string'],
            'user.phone' => ['required', 'phone:AUTO,GB'],
            'user.postcode' => ['required', 'string'],
            'user.gender' => ['required', new Enum(GenderEnum::class)]
        ];

        if (SiteEnum::belongsToOrganisation(OrganisationEnum::GWActive) || !app()->environment('production')) { // Only the email (user data) is collected for the RunThrough site.
            return array_merge($eec, [
                'user' => ['required'],
                'user.email' => ['required', 'string', 'email'],
                'user.first_name' => ['required', 'string'],
                'user.last_name' => ['required', 'string']
            ]);
        }

        return array_merge($eec, $user);
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'eec.*.ref.string' => 'The selected category must be a string.',
            'eec.*.ref.required' => 'The selected category is required.',
            'eec.*.ref.exists' => 'The selected category is invalid.',

            // 'eec.*.quantity.required' => 'The quantity is required.',
            // 'eec.*.quantity.integer' => 'The quantity must be an integer.',
            // 'eec.*.quantity.min' => 'The quantity must be at least 1.'
            // 'eec.*.ref.required' => [
            //     'value' => ':input',
            //     'message' => 'The selected category is required.'
            // ],
            // 'eec.*.ref.exists' => [
            //     'value' => ':input',
            //     'message' => 'The selected category is invalid.'
            // ],
            'user.first_name.required' => 'The first name field is required.',
            'user.last_name.required' => 'The last name field is d .',
            'user.email.required' => 'The email field is required.'
        ];
    }

    public function bodyParameters()
    {
        return [
            'eec.*.ref' => [
                'description' => '(required)',
                'example' => EventEventCategory::inRandomOrder()->value('ref')
            ],
            'eec.*.quantity' => [
                'example' => random_int(1, 10)
            ],
            'user' => [
                'email' => [
                    'description' => 'The participant\'s email address. Required with user is not authenticated. Only the email property is required for the RunThrough site.',
                    'example' => 'jameswhite@gmail.com'
                ],
                'first_name' => [
                    'description' => 'The participant\'s first name. Required with user is not authenticated.',
                    'example' => 'James'
                ],
                'last_name' => [
                    'description' => 'The participant\'s last name. Required with user is not authenticated.',
                    'example' => 'White'
                ],
                'gender' => [
                    'description' => "Must be one of ".implode(', ', array_column(GenderEnum::cases(), 'value')) . ". Required with user is not authenticated.",
                    'example' => GenderEnum::Male->value
                ],
                'phone' => [
                    'description' => "Required with user is not authenticated.",
                    'example' => '+447849675382'
                ],
                'save_payment_method' => [
                    'description' => "Whether or not to save the payment method. Must be boolean when present.",
                    'example' => 1
                ]
            ]
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if (auth('api')->check()) {
            $user = auth('api')->user()->load('profile');

            $this->merge([ // Set the enquiry data for authenticated users
                'user' => [
                    'first_name' => $this->first_name ?? ucwords(trim($user->first_name)),
                    'last_name' => $this->last_name ?? ucwords(trim($user->last_name)),
                    'email' => $this->email ?? trim($user->email),
                    'phone' => $this->phone ?? $user->phone,
                    'postcode' => $this->postcode ?? $user->profile?->postcode,
                    'gender' => $this->gender ?? $user->profile?->gender?->value
                ]
            ]);
        }

        // Format data collected for unauthenticated users

        if (SiteEnum::belongsToOrganisation(OrganisationEnum::GWActive) || !app()->environment('production')) { // Only the email is collected for the RunThrough site
            $this->merge([
                'user' => [
                    'first_name' => ucwords(trim($this->user['first_name'] ?? null)),
                    'last_name' => ucwords(trim($this->user['last_name'] ?? null)),
                    'email' => trim($this->user['email'] ?? null)
                ]
            ]);
        }
    }
}
