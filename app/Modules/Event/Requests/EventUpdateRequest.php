<?php

namespace App\Modules\Event\Requests;

use Auth;
use Illuminate\Support\Facades\DB;
use Rule;
use Carbon\Carbon;
use App\Traits\SiteTrait;
use App\Http\Helpers\AccountType;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

use App\Enums\RoleNameEnum;
use App\Enums\EventTypeEnum;
use App\Enums\EventReminderEnum;
use App\Enums\EventCharitiesEnum;
use App\Enums\SocialPlatformEnum;
use App\Enums\EventRouteInfoTypeEnum;
use App\Modules\Event\Enums\EventRegistrationMethodTypesEnum;

use App\Models\City;
use App\Models\Venue;
use App\Models\Region;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Role;
use App\Modules\Event\Models\Serie;
use App\Modules\Event\Models\Sponsor;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventCategory;

use App\Traits\ImageValidator;
use App\Traits\GalleryValidator;
use App\Traits\FaqCustomValidator;
use App\Traits\MetaCustomValidator;
use App\Traits\FailedValidationResponseTrait;

use App\Rules\IsValidWithdrawalDeadline;
use App\Rules\EnsureEntityBelongsToEntity;
use App\Rules\EnsureRegionBelongsToCountry;
use App\Rules\EnsureUploadDataExists;

use App\Traits\EventThirdPartyCreateRequestTrait;
use App\Traits\EventThirdPartyUpdateRequestTrait;

class EventUpdateRequest extends FormRequest
{
    use MetaCustomValidator,
        FaqCustomValidator,
        FailedValidationResponseTrait,
        SiteTrait,
        ImageValidator,
        GalleryValidator,
        EventThirdPartyCreateRequestTrait,
        EventThirdPartyUpdateRequestTrait;

    private $eventThirdPartyRequestType = null;

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
        $rules = [
            'name' => ['sometimes', 'required', 'string', function ($attribute, $value, $fail) { // Ensure the event does not exists for the site making the request
                $exists = Event::where('name', $this->name)
                    ->where('id', '!=', $this->event?->id)
                    ->whereHas('eventCategories', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        });
                    })->exists();

                if ($exists) {
                    $fail('An event with that name already exists.');
                }
            }],
            'slug' => ['sometimes', 'required', 'string', function ($attribute, $value, $fail) { // Ensure the event does not exists for the site making the request
                $exists = Event::where('slug', $this->slug)
                    ->where('id', '!=', $this->event?->id)
                    ->whereHas('eventCategories', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        });
                    })->exists();

                if ($exists) {
                    $fail('An event with that slug already exists.');
                }
            }],
            'description' => ['sometimes', 'required', 'string'],
            'event_managers' => [
                'sometimes',
                'array',
                Rule::exists('users', 'ref')->where(function ($query) { // User must exist and must have the Event Manager role
                    $query->whereIn('users.id', function($query) {
                        $query->select('event_managers.user_id')
                            ->from('event_managers')
                            ->whereIn('users.ref', $this->event_managers);
                    });
                }),
            ],
            'partner_event' => ['sometimes', 'required', 'boolean'],
            'location' => ['sometimes', 'required', 'array:address,latitude,longitude'],
            'location.address' => [Rule::requiredIf($this->location), 'string', 'max:255'],
            'location.latitude' => [Rule::requiredIf($this->location), 'numeric', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'location.longitude' => [Rule::requiredIf($this->location), 'numeric', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
            'country' => [
                'string',
                Rule::requiredIf($this->region == true)
            ],
            'region' => [
                'bail',
                'string',
                'nullable',
                Rule::requiredIf($this->city == true),
                function ($attribute, $value, $fail) {
                    // Split the comma-separated values into an array
                    $regions = explode(',', $value);

                    // Check if each region exists and belongs to the correct site
                    foreach ($regions as $regionRef) {
                        $exists = DB::table('regions')
                            ->where('ref', $regionRef)
                            ->when(!AccountType::isGeneralAdmin(), function ($query) {
                                // Restrict to the current site for non-general admins
                                $query->where('site_id', static::getSite()?->id);
                            })
                            ->exists();

                        if (!$exists) {
                            $fail("The region reference '{$regionRef}' is invalid or does not belong to the current site.");
                        }
                    }
                },
                new EnsureRegionBelongsToCountry, // Additional custom validation if needed
            ],
            'city' => [
                'bail',
                'string',
                'nullable',
                function ($attribute, $value, $fail) {
                    $cities = explode(',', $value); // Split CSV into an array
                    foreach ($cities as $city) {
                        $query = \DB::table('cities')->where('ref', $city);
                        if (! AccountType::isGeneralAdmin()) {
                            $query->where('site_id', static::getSite()?->id);
                        }
                        if (!$query->exists()) {
                            $fail("The selected {$attribute} is invalid: {$city}");
                        }
                    }
                }
            ],
            'venue' => [
                'bail',
                'sometimes',
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $venues = explode(',', $value); // Split CSV into an array
                    foreach ($venues as $venue) {
                        $query = \DB::table('venues')->where('ref', $venue);
                        if (! AccountType::isGeneralAdmin()) {
                            $query->where('site_id', static::getSite()?->id);
                        }
                        if (!$query->exists()) {
                            $fail("The selected {$attribute} is invalid: {$venue}");
                        }
                    }
                }
            ],
            // 'region' => [
            //     'bail',
            //     'string',
            //     Rule::requiredIf($this->city == true),
            //     Rule::exists('regions', 'ref')
            //         ->where(
            //             function ($query) {
            //                 if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
            //                     $query->where('site_id', static::getSite()?->id);
            //                 }
            //             }
            //         ),
            //     new EnsureRegionBelongsToCountry
            // ],
            // 'city' => [
            //     'bail',
            //     'string',
            //     'nullable',
            //     Rule::requiredIf($this->venue == true),
            //     Rule::exists('cities', 'ref')->where(
            //     function ($query) {
            //         if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
            //             $query->where('site_id', static::getSite()?->id);
            //         }
            //     }),
            //     new EnsureEntityBelongsToEntity(City::class, 'region', 'region'),
            // ],
            // 'venue' => [
            //     'bail',
            //     'sometimes',
            //     'nullable',
            //     'string',
            //     Rule::exists('venues', 'ref')->where(
            //     function ($query) {
            //         if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
            //             $query->where('site_id', static::getSite()?->id);
            //         }
            //     }),
            //     new EnsureEntityBelongsToEntity(Venue::class, 'city', 'city')
            // ],
            'serie' => [
                'bail',
                'sometimes',
                'nullable',
                'string',
                Rule::exists('series', 'ref')->where(
                function ($query) {
                    if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                        $query->where('site_id', static::getSite()?->id);
                    }
                })
            ],
            'sponsor' => [
                'bail',
                'sometimes',
                'nullable',
                'string',
                Rule::exists('sponsors', 'ref')->where(
                function ($query) {
                    if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                        $query->where('site_id', static::getSite()?->id);
                    }
                })
            ],
            'postcode' => ['sometimes', 'nullable', 'string'],
            'reminder' => ['sometimes', 'required', new Enum(EventReminderEnum::class)],
            'categories' => ['sometimes', 'required', 'array'],
            'categories.*.ref' => [
                'required',
                'string',
                'distinct',
                Rule::exists('event_categories', 'ref')->where(
                    function ($query) {
                        if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                            $query->where('site_id', static::getSite()?->id);
                        }
                    }),
                function ($attribute, $value, $fail) { // Check if a category currently linked to the event and having participant is not present in the request.
                    $this->event->load(['eventCategories' => function ($query) {
                        $query->withCount(['participants as participants_count' => function ($query) {
                            $query->where('event_event_category.event_id', $this->event->id);
                        }]);
                    }]);

                    $categories = collect($this->categories)->pluck('ref')->all();

                    foreach ($this->event->eventCategories as $category) {
                        if (! in_array($category->ref, $categories)) {
                            \Log::debug($category);
                            if ($category->participants_count) {
                                $fail('Sorry! You can\'t remove a category with participants from the event.');
                                // TODO: LOG a message to notify the developer's on slack
                            }
                        }
                    }
                }
            ],
            'categories.*.local_fee' => [
                'numeric',
                'min:0',
                'max:99999999999999'
            ],
            'categories.*.international_fee' => [
                'numeric',
                'min:0',
                'max:99999999999999'
            ],
            'categories.*.start_date' => [
                'required',
                'date_format:d-m-Y H:i:s',
            ],
            'categories.*.end_date' => [
                'required',
                'date_format:d-m-Y H:i:s',
                'after_or_equal:categories.*.start_date'
            ],
            'categories.*.registration_deadline' => [
                'nullable',
                'date_format:d-m-Y H:i:s',
                'before_or_equal:categories.*.start_date',
            ],
            'categories.*.withdrawal_deadline' => [
                'nullable',
                Rule::requiredIf($this->withdrawals == true),
                'date_format:d-m-Y H:i:s',
                'before:categories.*.registration_deadline',
                new IsValidWithdrawalDeadline
            ],
            'categories.*.total_places' => ['nullable', 'numeric', 'integer', 'min:1', 'max:4294967295'],
            'categories.*.classic_membership_places' => ['nullable', 'numeric', 'integer', 'min:1', 'max:4294967295'],
            'categories.*.premium_membership_places' => ['nullable', 'numeric', 'integer', 'min:1', 'max:4294967295'],
            'categories.*.two_year_membership_places' => ['nullable', 'numeric', 'integer', 'min:1', 'max:4294967295'],
            'type' => ['sometimes', 'required', new Enum(EventTypeEnum::class)],
            'charities' => ['required_with:_charities', new Enum(EventCharitiesEnum::class)],
            '_charities' => [
                'array',
                'exists:charities,ref',
                // Rule::excludeIf($this->charities && $this->charities == EventCharitiesEnum::All->value),
                Rule::prohibitedIf($this->charities && $this->charities == EventCharitiesEnum::All->value),
                Rule::requiredIf($this->charities && ($this->charities == EventCharitiesEnum::Included->value || $this->charities == EventCharitiesEnum::Excluded->value) ? true : false)
            ],
            'status' => ['sometimes', 'required', 'boolean'],
            'fundraising_emails' => ['sometimes', 'required', 'boolean'],
            'exclude_charities' => ['sometimes', 'required', 'boolean'],
            'exclude_website' => ['sometimes', 'required', 'boolean'],
            'exclude_participants' => ['sometimes', 'required', 'boolean'],
            'withdrawals' => ['sometimes', 'required', 'boolean'],
            'archived' => ['sometimes', 'required', 'boolean'],
            'route_info' => ['sometimes', 'required', 'array:description,type,code,media'],
            'route_info.description' => ['nullable', 'string', 'max:20000'],
            'route_info.type' => [
                'required_with:route_info.code,route_info.media',
                new Enum(EventRouteInfoTypeEnum::class)
            ],
            'route_info.code' => [
                'string',
                'nullable'
            ],
            'route_info.media' => [
                'array',
                Rule::requiredIf($this->route_info && isset($this->route_info['type']) && $this->route_info['type'] == EventRouteInfoTypeEnum::RouteImage->value),
                Rule::prohibitedIf($this->route_info && isset($this->route_info['type']) && $this->route_info['type'] != EventRouteInfoTypeEnum::RouteImage->value)
            ],
            'route_info.media.*' => ['string', new EnsureUploadDataExists()],
            'what_is_included' => ['sometimes', 'required', 'array:description,media'],
            'what_is_included.description' => ['nullable', 'string', 'max:20000'],
            'what_is_included.media' => ['sometimes', 'required', 'array'],
            'what_is_included.media.*' => ['string', new EnsureUploadDataExists()],
            'how_to_get_there' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'event_day_logistics' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'spectator_info' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'kit_list' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'estimated' => ['sometimes', 'required', 'boolean'],
            'charity_checkout_integration' => ['sometimes', 'required', 'boolean'],
            'terms_and_conditions' => ['sometimes', 'nullable', 'active_url'], // NB: Currently, only partner events (rfc, sfc, cfc) have an input field for terms_and_conditions link.
            'website' => ['sometimes', 'nullable', 'active_url'],
            'review' => ['sometimes', 'nullable', 'active_url'],
            'video' => ['sometimes', 'nullable', 'active_url'],
            'socials' => [
                'nullable',
            ],
            'socials.*.platform' => [
                'distinct',
                new Enum(SocialPlatformEnum::class),
                Rule::requiredIf($this->socials == true)
            ],
            'socials.*.url' => [
                'active_url',
                'distinct',
                Rule::requiredIf($this->socials == true)
            ],
            'registration_method' => [
                'sometimes',
                'required',
                'array:website_registration_method,portal_registration_method'
            ],
            'registration_method.website_registration_method' => [
                'required_with:registration_method.portal_registration_method',
                'string',
                new Enum(EventRegistrationMethodTypesEnum::class)
            ],
            'registration_method.portal_registration_method' => [
                'required_with:registration_method.website_registration_method',
                'string',
                new Enum(EventRegistrationMethodTypesEnum::class)
            ],
            ...$this->metaRules(),
            ...$this->faqUpdateRules($this->route('event')),
            ...$this->imageRules(),
            ...$this->galleryRules()
        ];

        $eventThirdPartiesCount = $this->event?->eventThirdParties->count();

        $thirdPartyCreateRules = ($eventThirdPartiesCount < 1 && ($this->third_parties || 
            ($this->registration_method &&
                ((isset($this->registration_method['portal_registration_method']) && $this->registration_method['portal_registration_method'] == EventRegistrationMethodTypesEnum::External->value) ||
                (isset($this->registration_method['website_registration_method']) && $this->registration_method['website_registration_method'] == EventRegistrationMethodTypesEnum::External->value)))));

        $thirdPartyUpdateRules = ($eventThirdPartiesCount > 0 && ($this->third_parties || 
            ($this->registration_method &&
                ((isset($this->registration_method['portal_registration_method']) && $this->registration_method['portal_registration_method'] == EventRegistrationMethodTypesEnum::External->value) ||
                (isset($this->registration_method['website_registration_method']) && $this->registration_method['website_registration_method'] == EventRegistrationMethodTypesEnum::External->value)))));

        if ($thirdPartyCreateRules) {
            $thirdPartyRules = $this->eventThirdPartyCreateRequestRules();
            $this->eventThirdPartyRequestType = 'create';
        } else if ($thirdPartyUpdateRules) {
            $thirdPartyRules = $this->eventThirdPartyUpdateRequestRules();
            $this->eventThirdPartyRequestType = 'update';
        } else {
            $thirdPartyRules = [];
        }

        return [...$rules, ...$thirdPartyRules];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() 
    {
        $thirdPartyMessages = $this->eventThirdPartyRequestType == 'create' ? $this->eventThirdPartyCreateRequestMessages() : ($this->eventThirdPartyRequestType == 'update' ? $this->eventThirdPartyUpdateRequestMessages() : []);

        $messages = [
            'name.unique' => 'An event with that name already exists.',
            'country.required' => 'The country is required when the region is present.',
            'region.required' => 'The region is required when the city is present.',
            'city.required' => 'The city is required when the venue is present.',
            'event_managers.exists' => 'The selected event managers are invalid.',
            'event_categories.required' => 'The event categories are required.',
            'charities.Illuminate\Validation\Rules\Enum' => 'The selected charity_option is invalid.',
            'charities.required_with' => 'The charity_option field is required when charities is present.',
            '_charities.array' => 'The charities must be an array.',
            '_charities.exists' => 'The selected included/excluded charities are invalid.',
            '_charities.prohibited' => 'The charities field is prohibited when charity_option is all.',
            '_charities.required' => 'The charities field is required when charity_option is '.EventCharitiesEnum::Included->value .' or '. EventCharitiesEnum::Excluded->value,
            // '_charities.required_if' => 'The charities field is required when charity_option is '.EventCharitiesEnum::Included->value .' or '. EventCharitiesEnum::Excluded->value,
            'categories.required' => 'The categories fields are required.',
            'categories.*.ref.required' => 'The selected category is required.',
            'categories.*.ref.string' => 'The category must be a string.',
            'categories.*.ref.distinct' => 'The category field has a duplicate value.',
            'categories.*.ref.exists' => 'The selected category is invalid.',
            'categories.*.local_fee.numeric' => 'The local fee must be a number.',
            'categories.*.local_fee.min' => 'The local fee must be at least :min.',
            'categories.*.local_fee.max' => 'The local fee must not be greater than :max.',
            'categories.*.international_fee.numeric' => 'The international fee must be a number.',
            'categories.*.international_fee.min' => 'The international fee must be at least :min.',
            'categories.*.international_fee.max' => 'The international fee must not be greater than :max.',
            'categories.*.start_date.required' => 'The start date field is required.',
            'categories.*.start_date.date_format' => 'The start date does not match the format :format.',
            'categories.*.start_date.after' => 'The start date must be a date after :date.',
            'categories.*.end_date.required' => 'The end date field is required.',
            'categories.*.end_date.date_format' => 'The end date does not match the format :format.',
            'categories.*.end_date.after_or_equal' => 'The end date must be a date after or equal to start date.',
            'categories.*.registration_deadline.date_format' => 'The registration deadline does not match the format :format.',
            'categories.*.registration_deadline.before_or_equal' => 'The registration deadline must be a date before or equal to start date.',
            'categories.*.registration_deadline.after' => 'The registration deadline must be a date after :date.',
            'categories.*.withdrawal_deadline.required' => 'The withdrawal deadline attribute is required when withdrawals is true.',
            'categories.*.withdrawal_deadline.date_format' => 'The withdrawal deadline does not match the format :format.',
            'categories.*.withdrawal_deadline.before' => 'The withdrawal deadline must be a date before registration deadline.',
            'categories.*.classic_membership_places.numeric' => 'The classic membership places must be a number.',
            'categories.*.classic_membership_places.integer' => 'The classic membership places must be a integer.',
            'categories.*.classic_membership_places.min' => 'The classic membership places must be at least :min.',
            'categories.*.classic_membership_places.max' => 'The classic membership places must not be greater than :max.',
            'categories.*.premium_membership_places.numeric' => 'The premium membership places must be a number.',
            'categories.*.premium_membership_places.integer' => 'The premium membership places must be a integer.',
            'categories.*.premium_membership_places.min' => 'The premium membership places must be at least :min.',
            'categories.*.premium_membership_places.max' => 'The premium membership places must not be greater than :max.',
            'categories.*.two_year_membership_places.numeric' => 'The two_year membership places must be a number.',
            'categories.*.two_year_membership_places.integer' => 'The two_year membership places must be a integer.',
            'categories.*.two_year_membership_places.min' => 'The two_year membership places must be at least :min.',
            'categories.*.two_year_membership_places.max' => 'The two_year membership places must not be greater than :max.',
            'route_info.type.required_with' => 'The route info type field is required when route info code or route info media is present.',
            'route_info.type.Illuminate\Validation\Rules\Enum' => 'The selected route info type is invalid.',
            'route_info.media.required' => 'The route info media field is required when type is route image(s).',
            'route_info.media.prohibited' => 'The route info media field is prohibited when type is not route image(s).',
            'socials.*.platform.Illuminate\Validation\Rules\Enum' => 'The selected platform is invalid.',
            'socials.*.platform.required' => 'The platform field is required.',
            'socials.*.platform.distinct' => 'The platform field has a duplicate value.',
            'socials.*.url.required' => 'The url field is required.',
            'socials.*.url.distinct' => 'The url field has a duplicate value.',
            'socials.*.url.active_url' => 'The url is not a valid URL.',
            ...$this->metaMessages(),
            ...$this->faqMessages(),
            ...$this->imageMessages(),
            ...$this->galleryMessages(),
            // 'sites.*.distinct' => 'The sites field has a duplicate value.',
        ];

        return [...$messages, ...$thirdPartyMessages];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The name of the event',
                'example' => 'Bath Half Marathon '.random_int(0, 1000),
            ],
            'description' => [
                'description' => 'The event description',
                'example' => 'The event description',
            ],
            'event_managers' => [
                'description' => 'The refs of the event managers. Must be one of '.implode(', ', User::inRandomOrder()
                    ->whereHas('roles', function ($query) {
                        $query->where('name', RoleNameEnum::EventManager);
                    })->limit(3)->pluck('ref')->toArray()),
                'example' => User::inRandomOrder()
                    ->whereHas('roles', function ($query) {
                        $query->where('name', RoleNameEnum::EventManager);
                    })->limit(3)->pluck('ref')
            ],
            'categories.*.ref' => [
                'example' => EventCategory::inRandomOrder()->value('ref')
            ],
            'categories.*.local_fee' => [
                'example' => random_int(12, 100)
            ],
            'categories.*.international_fee' => [
                'example' => random_int(12, 100)
            ],
            'categories.*.start_date' => [
                'example' => Carbon::now()->addWeeks(6)->format('d-m-Y H:i:s')
            ],
            'categories.*.end_date' => [
                'example' => Carbon::now()->addWeeks(6)->format('d-m-Y H:i:s')
            ],
            'categories.*.registration_deadline' => [
                'example' => Carbon::now()->addWeeks(4)->format('d-m-Y H:i:s')
            ],
            'categories.*.withdrawal_deadline' => [
                'example' => Carbon::now()->addWeeks(3)->format('d-m-Y H:i:s')
            ],
            'address' => [
                'description' => 'The event address',
                'example' => 'Fourth Floor, Maya House, 134-138 Borough High St'
            ],
            'region' => [
                'description' => "The ref of the region. Must be one of ".implode(', ', Region::inRandomOrder()->where('site_id', static::getSite()?->id)->limit(3)->pluck('ref')->all()),
                'example' => Region::inRandomOrder()
                    ->where('site_id', static::getSite()?->id)
                    ->value('ref')
            ],
            'city' => [
                'description' => "The ref of the city (required). Must be one of ".implode(', ', City::inRandomOrder()->where('site_id', static::getSite()?->id)->limit(3)->pluck('ref')->all()),
                'example' => City::inRandomOrder()
                    ->where('site_id', static::getSite()?->id)
                    ->value('ref')
            ],
            'venue' => [
                'description' => "The ref of the venue (required). Must be one of ".implode(', ', Venue::inRandomOrder()->where('site_id', static::getSite()?->id)->limit(3)->pluck('ref')->all()),
                'example' => Venue::inRandomOrder()
                    ->where('site_id', static::getSite()?->id)
                    ->value('ref')
            ],
            'serie' => [
                'description' => "The ref of the serie (required). Must be one of ".implode(', ', Serie::inRandomOrder()->where('site_id', static::getSite()?->id)->limit(3)->pluck('ref')->all()),
                'example' => Serie::inRandomOrder()
                    ->where('site_id', static::getSite()?->id)
                    ->value('ref')
            ],
            'sponsor' => [
                'description' => "The ref of the sponsor (required). Must be one of ".implode(', ', Sponsor::inRandomOrder()->where('site_id', static::getSite()?->id)->limit(3)->pluck('ref')->all()),
                'example' => Sponsor::inRandomOrder()
                    ->where('site_id', static::getSite()?->id)
                    ->value('ref')
            ],
            'postcode' => [
                'example' => 'SE1 1LB'
            ],
            'country' => [
                'example' => 'England'
            ],
            'reminder' => [
                'description' => "The frequency at which the event participants should be reminded. Must be one of ".implode(', ', array_column(EventReminderEnum::cases(), 'value')),
                'example' => EventReminderEnum::Daily->value
            ],
            'type' => [
                'description' => "The type of the event. Must be one of ".implode(', ', array_column(EventTypeEnum::cases(), 'value')),
                'example' => array_column(EventTypeEnum::cases(), 'value')[0]
            ],
            'charities' => [
                'description' => "Whether the event is accessible by all charities, only the charities included or it is not accessible by excluded charities. Must be one of ".implode(', ', array_column(EventCharitiesEnum::cases(), 'value')),
                'example' => array_column(EventCharitiesEnum::cases(), 'value')[0]
            ],
            '_charities' => [
                'description' => "An array of the ref of included/excluded charities. Only set this if the charities parameter is set to included or excluded. Must be one of ".implode(', ', Charity::inRandomOrder()->where('status', Charity::ACTIVE)->limit(5)->pluck('ref')->all()),
                'example' => Charity::inRandomOrder()->where('status', Charity::ACTIVE)->limit(5)->pluck('ref')->all()
            ],
            'total_places' => [
                'description' => 'The total number of participants the event want',
            ],
            'video' => [
                'description' => 'The url to the event video',
            ],
            'review' => [
                'description' => 'The url to the reviews of the event',
            ],
            'socials.*.platform' => [
                'example' => 'twitter'
            ],
            'socials.*.url' => [
                'example' => 'https://twitter.com/bathhalf'
            ],
            ...$this->eventThirdPartyUpdateRequestBodyParameters(),
            ...$this->metaBodyParameters(),
            ...$this->faqUpdateBodyParameters(),
            ...$this->imageBodyParameters(),
            ...$this->galleryBodyParameters()
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $data = [];

        if ($this->name) {
            $data['name'] = ucfirst(trim($this->name));
        }

        $this->merge($data);
    }
}
