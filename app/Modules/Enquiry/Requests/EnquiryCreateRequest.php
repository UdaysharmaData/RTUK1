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
use App\Modules\Event\Models\EventCategory;
use App\Traits\FailedValidationResponseTrait;
use App\Modules\Enquiry\Models\ExternalEnquiry;

class EnquiryCreateRequest extends FormRequest
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
            'email' => ['required', 'string', 'email'],
            'first_name' => ['sometimes', 'nullable', 'string'],
            'last_name' => ['sometimes', 'nullable', 'string'],
            'phone' => ['sometimes', 'nullable', 'phone:AUTO,GB'],
            'postcode' => ['sometimes', 'nullable', 'string'],
            'gender' => ['sometimes', 'nullable', new Enum(GenderEnum::class)],
            'comments' => ['sometimes', 'nullable', 'string'],

            'site' => ['required', Rule::exists('sites', 'ref')->where(
                function ($query) {
                    if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                        $query->where("id", static::getSite()?->id);
                    }
                })],
            'charity' => ['sometimes', 'nullable', Rule::exists('charities', 'ref')],
            'event' => [
                'required',
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
            'event_category' => [
                'required',
                Rule::exists('event_categories', 'ref')->where(function ($query) { // Ensure the event category is associated with the event and belongs to the site in/making the request
                    $query->whereIn('event_categories.id', function($query) {
                        $query->select('event_event_category.event_category_id')
                            ->from('event_event_category')
                            ->whereIn('event_event_category.event_id', function ($query) {
                                $query->select('events.id')
                                    ->from('events')
                                    ->where('events.ref', $this->event);
                            })
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
            'external_enquiry' => [
                'sometimes',
                'nullable',
                Rule::exists('external_enquiries', 'ref')
            ],

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
            'event.required' => 'The event field is required when category is present.',
            'event_category.required' => 'The category field is required when event is present.',
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
                'description' => 'The ref of the event. Must be one of '. implode(', ', Event::inRandomOrder()->limit(10)->get()->pluck('ref')->toArray()),
                'example' => Event::inRandomOrder()->value('ref')
            ],
            'event_category' => [
                'description' => 'The ref of the event category. Must be one of '. implode(', ', EventCategory::inRandomOrder()->limit(10)->get()->pluck('ref')->toArray()),
                'example' => EventCategory::inRandomOrder()->value('ref')
            ],
            'external_enquiry' => [
                'description' => 'The ref of the external enquiry. Must be one of '. implode(', ', ExternalEnquiry::inRandomOrder()->limit(10)->get()->pluck('ref')->toArray()),
                'example' => ExternalEnquiry::inRandomOrder()->value('ref')
            ],
            'gender' => [
                'description' => "Must be one of ".implode(', ', array_column(GenderEnum::cases(), 'value')),
                'example' => GenderEnum::Male->value
            ],
            'phone' => [
                'example' => '+447849675382'
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
        $data = [];

        if (isset($this->email)) {
            $data['email'] = trim($this->email);
        }

        if (isset($this->name)) {
            $data['name'] = ucwords(trim($this->name));
        }

        $this->merge($data);
    }
}
