<?php

namespace App\Traits;

use Rule;
use App\Traits\SiteTrait;
use App\Http\Helpers\AccountType;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Event\Models\EventThirdParty;
use App\Modules\Partner\Models\PartnerChannel;

trait EventThirdPartyUpdateRequestTrait
{
    use SiteTrait;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function eventThirdPartyUpdateRequestRules()
    {
        return [
            'third_parties' => [
                'required',
                'array'
            ],
            'third_parties.*.ref' => [
                'bail',
                'required',
                'string',
                'distinct',
                Rule::exists('event_third_parties', 'ref')->where( // Ensure the ref belongs to the given site
                    function ($query) {
                        $query->whereIn('event_third_parties.event_id', function ($query) {
                            $query->select('event_event_category.event_id')
                                ->from('event_event_category')
                                ->whereIn('event_event_category.event_category_id', function ($query) {
                                    $query->select('event_categories.id')
                                        ->from('event_categories')
                                        ->where('event_categories.site_id', static::getSite()?->id);
                                });
                        });
                    }),
                    function ($attribute, $value, $fail) { // Ensure the ref belongs to the given event
                        $exists = EventThirdParty::where('ref', $value)
                            ->where('event_id', $this->event->id)
                            ->exists();

                        if (! $exists) {
                            $fail('The ref does not belong to those associated with the event.');
                        }
                    },
            ],
            'third_parties.*.external_id' => [/*'sometimes', */'required', 'alpha_num'],
            'third_parties.*.occurrence_id_not_set_on_third_party' => [
                'sometimes',
                'required',
                'boolean'
            ],
            'third_parties.*.occurrence_id' => [
                'alpha_num',
                'nullable',
                Rule::requiredIf(isset($this->third_parties) && $this->third_parties[0] && (!isset($this->third_parties[0]['occurrence_id_not_set_on_third_party']) || (isset($this->third_parties[0]['occurrence_id_not_set_on_third_party']) && empty($this->third_parties[0]['occurrence_id_not_set_on_third_party']))))
            ],
            'third_parties.*.partner_channel' => ['bail', 'sometimes', 'required', 'string', Rule::exists('partner_channels', 'ref')->where(function ($query) { // The partner channel must exists and belong to the site making the request
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
            })],
            'third_parties.*.categories' => ['sometimes', 'required'],
            'third_parties.*.categories.*.ref' => [
                'bail',
                'required',
                'string',
                'distinct',
                Rule::exists('event_categories', 'ref')->where(
                    function ($query) {
                        if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                            $query->where('site_id', static::getSite()?->id);
                        }

                        // $query->whereIn('event_categories.id', function ($query) {
                        //     $query->select('event_category_id')
                        //         ->from('event_event_category')
                        //         ->where('event_id', $this->event->id);
                        // });
                    }),
                function ($attribute, $value, $fail) { // Ensure the event category is among those selected for the event
                    if (!in_array($value, collect($this->categories)->pluck('ref')->toArray())) {
                        return $fail('The selected category is not among the categories selected for the event.');
                    }

                    if (count($this->categories) != count($this->third_parties[0]['categories'])) { // Ensure the count of the first third party is the same as the count of categories
                        return $fail('The count of event categories does not match that of third parties.');
                    }
                }
            ],
            'third_parties.*.categories.*.external_id' => [
                'required',
                'alpha_num'
            ]
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function eventThirdPartyUpdateRequestMessages() {
        return [
            'third_parties.required' => 'The third parties are required.',
            'third_parties.*.required' => 'The third party is required.',
            'third_parties.*.external_id.required' => 'The external id is required.',
            'third_parties.*.occurrence_id.alpha_num' => 'The occurrence_id must only contain letters and numbers.',
            'third_parties.*.ref.required' => 'The ref field is required.',
            'third_parties.*.ref.distinct' => 'The ref field has a duplicate value.',
            'third_parties.*.ref.exists' => 'The selected ref is invalid.',
            'third_parties.*.partner_channel.exists' => 'The selected partner channel is invalid.',
            'third_parties.*.partner_channel.string' => 'The partner channel must be a string.',
            'third_parties.*.categories.*.ref.required' => 'The selected category is required.',
            'third_parties.*.categories.*.ref.string' => 'The category must be a string.',
            'third_parties.*.categories.*.ref.exists' => 'The selected category is invalid.',
            'third_parties.*.categories.*.ref.distinct' => 'The ref field has a duplicate value.',
            'third_parties.*.categories.*.external_id.alpha_num' => 'The external_id must only contain letters and numbers.'
        ];
    }

    public function eventThirdPartyUpdateRequestBodyParameters()
    {
        return [
            'third_parties.*.external_id' => [
                'example' => random_int(1000, 9999999999)
            ],
            'third_parties.*.occurrence_id_not_set_on_third_party' => [
                'description' => "When set to true, the occurrence_id is expected to be null.",
                'example' => false
            ],
            'third_parties.*.occurrence_id' => [
                'description' => "The occurrence_id reprensents the session number (year) for the given event on LDT",
                'example' => random_int(1000, 9999999999)
            ],
            'third_parties.*.partner_channel' => [
                'description' => "The partner channel. Must be one of ".implode(', ', PartnerChannel::whereHas('partner', function ($query) {
                    $query->where('site_id', static::getSite()?->id);
                })->limit(3)->pluck('ref')->all()),
                'example' => PartnerChannel::inRandomOrder()
                    ->whereHas('partner', function ($query) {
                        $query->where('site_id', static::getSite()?->id);
                    })->value('ref')
            ],
            'third_parties.*.categories.*.ref' => [
                'description' => "The event category (for the given event). Must be one of ".implode(', ', EventCategory::inRandomOrder()->limit(3)->pluck('ref')->all()),
                'example' => EventCategory::inRandomOrder()->value('ref')
            ],
            'third_parties.*.categories.*.external_id' => [
                'description' => "The equivalence of the event category above on the third party platform (for the given event)",
                'example' => random_int(1000, 9999999999)
            ]
        ];
    }
}
