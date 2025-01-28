<?php

namespace App\Modules\Event\Requests;

use Rule;
use App\Traits\SiteTrait;
use App\Http\Requests\PaginationRequest;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class EventClientListingQueryParamsRequest extends FormRequest
{
    use FailedValidationResponseTrait, SiteTrait;

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
            'start_date' => ['sometimes', 'nullable', 'date_format:d-m-Y'],
            'end_date' => ['sometimes', 'nullable', 'date_format:d-m-Y', 'after_or_equal:start_date'],
            'price' => ['sometimes', 'nullable', 'array', 'size:2'],
            'price.*' => ['integer'],
            'city' => ['sometimes', 'nullable', Rule::exists('cities', 'ref')->where(
                function ($query) {
                    return $query->where("site_id", static::getSite()?->id);
                })],
            'venue' => ['sometimes', 'nullable', Rule::exists('venues', 'ref')->where(
                function ($query) {
                    return $query->where("site_id", static::getSite()?->id);
                })],
            'region' => ['sometimes', 'nullable', 'string', Rule::exists('regions', 'ref')->where(
                function ($query) {
                    return $query->where("site_id", static::getSite()?->id);
                })],
            'location' => ['sometimes', 'nullable', 'array:latitude,longitude'],
            'location.latitude' => [Rule::requiredIf($this->location), 'numeric'],
            'location.longitude' => [Rule::requiredIf($this->location), 'numeric'],
            'radius' => ['sometimes', 'nullable', 'array', 'size:2'],
            'radius.*' => ['integer'],
            'virtual_events' => ['sometimes', 'nullable', 'in:include,exclude,only'],
            'date' => ['nullable', 'string'],
            'skip' => ['sometimes', 'nullable', 'numeric', 'integer'],
            'take' => ['numeric', 'integer', Rule::requiredIf((bool) $this->skip)],
            ...(new PaginationRequest($this->request->all()))->rules()
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
            'take.required' => 'The take param is required when skip is present.',
        ];
    }
}
