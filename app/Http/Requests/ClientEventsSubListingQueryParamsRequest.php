<?php

namespace App\Http\Requests;

use App\Traits\SiteTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientEventsSubListingQueryParamsRequest extends FormRequest
{
    use SiteTrait;

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
            'name' => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', 'nullable', Rule::exists('event_categories', 'ref')],
            'region' => ['sometimes', 'nullable', 'string', Rule::exists('regions', 'ref')
                ->where(fn($query) => $query->where("site_id", static::getSite()?->id))
            ],
            'city' => ['sometimes', 'nullable', 'string', Rule::exists('cities', 'ref')
                ->where(fn($query) => $query->where("site_id", static::getSite()?->id))
            ],
            'venue' => ['sometimes', 'nullable', 'string', Rule::exists('venues', 'ref')
                ->where(fn($query) => $query->where("site_id", static::getSite()?->id))
            ],
            'dates' => ['sometimes', 'nullable', 'array', 'size:2'],
            'dates.*' => ['string', 'date_format:d-m-Y'],
            'price' => ['sometimes', 'nullable', 'array', 'size:2'],
            'price.*' => ['integer'],
            'address' => ['sometimes', 'nullable', 'string'],
            'virtual_events' => ['sometimes', 'nullable', 'in:include,exclude,only'],
            'date' => ['nullable', 'string'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ];
    }
}
