<?php

namespace App\Modules\Event\Requests;

use Rule;
use App\Enums\ListTypeEnum;
use App\Enums\EventTypeEnum;
use App\Enums\EventStateEnum;
use App\Enums\ListDraftedItemsOptionsEnum;
use Illuminate\Validation\Rules\Enum;
use App\Rules\EnsureEntityBelongsToEntity;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\EnsureRegionBelongsToCountry;
use App\Enums\ListingFaqsFilterOptionsEnum;
use App\Enums\ListingMedalsFilterOptionsEnum;
use App\Traits\OrderByParamValidationClosure;
use App\Traits\FailedValidationResponseTrait;
use App\Enums\ListSoftDeletedItemsOptionsEnum;
use App\Http\Helpers\AccountType;
use App\Models\City;
use App\Models\Region;
use App\Models\Venue;
use App\Traits\SiteTrait;

class EventListingQueryParamsRequest extends FormRequest
{
    use FailedValidationResponseTrait,
        OrderByParamValidationClosure,
        SiteTrait;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'term' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', 'boolean'],
            'partner_event' => ['sometimes', 'nullable', 'boolean'],
            'year' => ['sometimes', 'nullable', 'digits:4', 'date_format:Y'],
            'month' => ['sometimes', 'nullable', 'numeric', 'min:1', 'max:12'],
            'state' => ['sometimes', 'nullable', new Enum(EventStateEnum::class)],
            'site' => ['sometimes', 'nullable', Rule::exists('sites', 'ref')],
            'category' => ['sometimes', 'nullable', Rule::exists('event_categories', 'ref')],
            'country' => [
                'string',
                Rule::requiredIf($this->region == true)
            ],
            'region' => [
                'bail',
                'string',
                Rule::requiredIf($this->city == true),
                Rule::exists('regions', 'ref')
                    ->where(
                        function ($query) {
                            if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                                $query->where('site_id', static::getSite()?->id);
                            }
                        }
                    ),
                new EnsureRegionBelongsToCountry
            ],
            'city' => [
                'bail',
                'string',
                Rule::requiredIf($this->venue == true),
                Rule::exists('cities', 'ref')
                    ->where(
                        function ($query) {
                            if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                                $query->where('site_id', static::getSite()?->id);
                            }
                        }
                    ),
                new EnsureEntityBelongsToEntity(City::class, 'region', 'region')
            ],
            'venue' => [
                'bail',
                'sometimes',
                'nullable',
                'string',
                Rule::exists('venues', 'ref')
                    ->where(
                        function ($query) {
                            if (! AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                                $query->where('site_id', static::getSite()?->id);
                            }
                        }
                    ),
                new EnsureEntityBelongsToEntity(Venue::class, 'city', 'city')
            ],
            'experience' => ['sometimes', 'nullable', Rule::exists('experiences', 'ref')],
            'type' => ['sometimes', new Enum(EventTypeEnum::class)],
            'faqs' => ['sometimes', new Enum(ListingFaqsFilterOptionsEnum::class)],
            'medals' => ['sometimes', new Enum(ListingMedalsFilterOptionsEnum::class)],
            'ids' => ['sometimes', 'array', Rule::exists('events', 'id')],
            'deleted' => ['sometimes', new Enum(ListSoftDeletedItemsOptionsEnum::class)],
            'drafted' => ['sometimes', new Enum(ListDraftedItemsOptionsEnum::class)],
            'order_by' => ['sometimes', 'array'],
            'order_by.*' => ['string', $this->isValidOrderByParameter(ListTypeEnum::Events)],
            'has_third_party_set_up' => ['sometimes', 'nullable', 'boolean'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() 
    {
        return [
            'country.required' => 'The country is required when the region is present.',
            'region.required' => 'The region is required when the city is present.',
            'city.required' => 'The region is required when the venue is present.'
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->_prepareForValidation();
    }

    /**
     * @return void
     */
    protected function passedValidation(): void
    {
        $this->_passedValidation();
    }
}
