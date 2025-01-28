<?php

namespace App\Modules\Event\Requests;

use Auth;
use Rule;
use App\Traits\SiteTrait;
use App\Http\Helpers\AccountType;
 
use App\Modules\Partner\Models\Partner;
use App\Modules\Event\Models\EventCategory;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use App\Modules\Partner\Models\PartnerChannel;

class EventThirdPartyCreateRequest extends FormRequest
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
            'external_id' => ['required', 'alpha_num'],
            'occurrence_id_not_set_on_third_party' => [
                'sometimes',
                'required',
                'boolean'
            ],
            'occurrence_id' => [
                'alpha_num',
                'nullable',
                Rule::requiredIf(!isset($this->occurrence_id_not_set_on_third_party) || (isset($this->occurrence_id_not_set_on_third_party) && empty($this->occurrence_id_not_set_on_third_party)))
            ],
            'partner_channel' => ['bail', 'required', 'string', Rule::exists('partner_channels', 'ref')->where(function ($query) { // The partner channel must exists and belong to the site making the request
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
            'categories' => ['required'],
            'categories.*.ref' => [
                'required',
                'string',
                'distinct',
                Rule::exists('event_categories', 'ref')->where(
                    function ($query) {
                        if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                            $query->where('site_id', static::getSite()?->id);
                        }

                        $query->whereIn('event_categories.id', function ($query) {
                            $query->select('event_category_id')
                                ->from('event_event_category')
                                ->where('event_id', $this->event->id);
                        });
                    })
            ],
            'categories.*.external_id' => [
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
    public function messages() {
        return [
            'partner_channel.exists' => 'The selected partner channel is invalid.',
            'partner_channel.string' => 'The partner channel must be a string.',
            'occurrence_id.alpha_num' => 'The occurrence_id must only contain letters and numbers.',
            'categories.*.ref.required' => 'The selected category is required.',
            'categories.*.ref.string' => 'The category must be a string.',
            'categories.*.ref.exists' => 'The selected category is invalid.',
            'categories.*.ref.distinct' => 'The ref field has a duplicate value.',
            'categories.*.external_id.alpha_num' => 'The external_id must only contain letters and numbers.'
        ];
    }
    
    public function bodyParameters()
    {
        return [
            'external_id' => [
                'example' => random_int(1000, 9999999999)
            ],
            'occurrence_id_not_set_on_third_party' => [
                'description' => "When set to true, the occurrence_id is expected to be null.",
                'example' => false
            ],
            'occurrence_id' => [
                'description' => "The occurrence_id reprensents the session number (year) for the given event on LDT",
                'example' => random_int(1000, 9999999999)
            ],
            'partner_channel' => [
                'description' => "The partner channel. Must be one of ".implode(', ', PartnerChannel::whereHas('partner', function ($query) {
                    $query->where('site_id', static::getSite()?->id);
                })->limit(3)->pluck('ref')->all()),
                'example' => PartnerChannel::inRandomOrder()
                    ->whereHas('partner', function ($query) {
                        $query->where('site_id', static::getSite()?->id);
                    })->value('ref')
            ],
            'categories.*.ref' => [
                'description' => "The event category (for the given event). Must be one of ".implode(', ', EventCategory::inRandomOrder()->limit(3)->pluck('ref')->all()),
                'example' => EventCategory::inRandomOrder()->value('ref')
            ],
            'categories.*.external_id' => [
                'description' => "The equivalence of the event category above on the third party platform (for the given event)",
                'example' => random_int(1000, 9999999999)
            ]
        ];
    }
}
