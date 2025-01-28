<?php

namespace App\Modules\Enquiry\Requests;

use Auth;
use Rule;
use App\Traits\SiteTrait;
use App\Enums\GenderEnum;
use App\Http\Helpers\AccountType;
 
use App\Modules\Event\Models\Event;
use App\Modules\Setting\Models\Site;
use Illuminate\Validation\Rules\Enum;
use App\Modules\Charity\Models\Charity;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use App\Modules\Partner\Models\PartnerChannel;
use App\Modules\Event\Models\EventCategoryEventThirdParty;

class ExternalEnquiryUpdateRequest extends FormRequest
{
    use FailedValidationResponseTrait, SiteTrait;

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
            // Validate user
            'email' => ['sometimes', 'required', 'string', 'email'],
            'first_name' => ['sometimes', 'nullable', 'string'],
            'last_name' => ['sometimes', 'nullable', 'string'],
            'phone' => ['sometimes', 'nullable', 'phone:AUTO,GB'],
            'postcode' => ['sometimes', 'nullable', 'string'],
            'address' => ['sometimes', 'nullable', 'string'],
            'city' => ['sometimes', 'nullable', 'string'],
            'region' => ['sometimes', 'nullable', 'string'],
            'country' => ['sometimes', 'nullable', 'string'],
            'gender' => ['sometimes', 'nullable', new Enum(GenderEnum::class)],
            'dob' => ['sometimes', 'nullable', 'date_format:d-m-Y'],
            'emergency_contact_name' => ['sometimes', 'nullable', 'string'],
            'emergency_contact_phone' => ['sometimes', 'nullable', 'phone:AUTO,GB'],
            'emergency_contact_relationship' => ['sometimes', 'nullable', 'string'],
            'comments' => ['sometimes', 'nullable', 'string'],

            'site' => [
                'bail',
                Rule::requiredIf($this->event == true),
                Rule::exists('sites', 'ref')->where(
                    function ($query) {
                        if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                            $query->where("id", static::getSite()?->id);
                        }
                    })
            ],
            'charity' => ['sometimes', 'nullable', Rule::exists('charities', 'ref')],
            'event' => [
                'bail',
                Rule::requiredIf($this->partner_channel == true),
                Rule::exists('events', 'ref')->where(function ($query) { // Ensure the event belongs to the site in/making the request
                    $query->whereIn('events.id', function($query) {
                        $query->select('event_event_category.event_id')
                            ->from('event_event_category')
                            ->whereIn('event_event_category.event_category_id', function ($query) {
                                $query->select('event_categories.id')
                                    ->from('event_categories')
                                    ->whereIn('event_categories.site_id', function ($query) {
                                        $query->select('sites.id')
                                            ->from('sites')
                                            ->where('sites.ref', $this->site);
                                    });
                            });
                    });
                })
            ],
            'partner_channel' => [
                'bail',
                Rule::requiredIf($this->event_category_event_third_party == true),
                Rule::exists('partner_channels', 'ref')->where(function ($query) {
                    if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                        $query->whereIn('partner_channels.partner_id', function($query) {
                            $query->select('partners.id')
                                ->from('partners')
                                ->whereIn('partners.site_id', function ($query) {
                                    $query->select('sites.id')
                                        ->from('sites')
                                        ->where('sites.id', static::getSite()?->id);
                                });
                        });
                    }
                }),
                // function ($attribute, $value, $fail) { // TODO: Take a deeper look at this to know whether this constraint is important. Since the channels of an event can change,
                //     if ($this->event && PartnerChannel::where('ref', $value)
                //         ->whereHas('eventThirdParties', function ($query) {
                //             $query->whereHas('event', function ($query) {
                //                 $query->where('ref', $this->event);
                //             });
                //     })->doesntExist()) {
                //         $fail('The selected partner channel is invalid.');
                //     }
                // }
            ],
            'event_category_event_third_party' => [
                'bail',
                'sometimes',
                'nullable',
                Rule::exists('event_category_event_third_party', 'ref'),
                function ($attribute, $value, $fail) {
                    if ($this->event /*&& $this->partner_channel*/ && EventCategoryEventThirdParty::where('ref', $value)
                        ->whereHas('eventThirdParty', function ($query) {
                            // $query->whereHas('partnerChannel', function ($query) {
                            //     $query->where('ref', $this->partner_channel);
                            // });
                            $query->whereHas('event', function ($query) {
                                $query->where('ref', $this->event);
                            });
                    })->doesntExist()) {
                        $fail('The selected event category is invalid.');
                    }
                }
            ],
            'channel_record_id' => ['sometimes', 'nullable', 'string'],

            'contacted' => ['sometimes', 'required', 'boolean'],
            'converted' => ['sometimes', 'required', 'boolean']
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'site.required' => 'The site field is required when event is present.',
            'event.required' => 'The event field is required when partner channel is present.',
            'partner_channel.required' => 'The partner channel field is required when event category field is present.'
        ];
    }

    public function bodyParameters()
    {
        return [
            'first_name' => [
                'description' => 'The participant\'s first name',
                'example' => 'James'
            ],
            'last_name' => [
                'description' => 'The participant\'s last name',
                'example' => 'White'
            ],
            'site' => [
                'description' => 'The ref of the site. Must be one of '. implode(', ', Site::inRandomOrder()->limit(5)->get()->pluck('ref')->toArray()),
                'example' => Site::inRandomOrder()->value('ref')
            ],
            'charity' => [
                'description' => 'The ref of the charity. Must be one of '. implode(', ', Charity::inRandomOrder()->limit(10)->get()->pluck('ref')->toArray()),
                'example' => Charity::inRandomOrder()->value('ref')
            ],
            'event' => [
                'description' => 'The ref of the event. Is required when partner channel field is present. Must be one of '. implode(', ', Event::inRandomOrder()->limit(10)->get()->pluck('ref')->toArray()),
                'example' => Event::inRandomOrder()->value('ref')
            ],
            'partner_channel' => [
                'description' => 'The ref of the partner channel. Is required when event category field (event_category_event_third_party) is present. Must be one of '. implode(', ', PartnerChannel::inRandomOrder()->limit(10)->get()->pluck('ref')->toArray()),
                'example' => PartnerChannel::inRandomOrder()->value('ref')
            ],
            'event_category_event_third_party' => [
                'description' => 'The ref of the event category event third party. Must be one of '. implode(', ', EventCategoryEventThirdParty::inRandomOrder()->limit(10)->get()->pluck('ref')->toArray()),
                'example' => EventCategoryEventThirdParty::inRandomOrder()->value('ref')
            ],
            'channel_record_id' => [
                'description' => 'The unique identifier of the external enquiries fetched from the channel',
                'example' => '5fb519821e28346daef1a9e5'
            ],
            'gender' => [
                'description' => "Must be one of ".implode(', ', array_column(GenderEnum::cases(), 'value')),
                'example' => GenderEnum::Male->value
            ],
            'phone' => [
                'example' => '+447849675382'
            ],
            'emergency_contact_name' => [
                'example' => 'Mary Lane'
            ],
            'emergency_contact_phone' => [
                'example' => '+447896884785'
            ],
            'emergency_contact_relationship' => [
                'example' => 'Spouse'
            ],
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

        if (isset($this->name)) {
            $data['name'] = ucwords(trim($this->name));
        }

        if (isset($this->name)) {
            $data['email'] = trim($this->email);
        }

        $this->merge([
            ...$data
        ]);
    }
}
