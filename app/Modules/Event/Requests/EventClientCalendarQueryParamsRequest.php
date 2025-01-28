<?php

namespace App\Modules\Event\Requests;

use Rule;
use App\Traits\SiteTrait;
use App\Modules\Setting\Models\Site;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class EventClientCalendarQueryParamsRequest extends FormRequest
{
    use FailedValidationResponseTrait, SiteTrait;

    protected ?Site $site;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->site = static::getSite();

        parent::__construct();
    }

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
            'month_year' => ['required', 'date_format:m-Y'],
            'name' => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', 'nullable', 'string', Rule::exists('event_categories', 'ref')->where(
                function ($query) {
                    return $query->where("site_id",  $this->site?->id);
                })],
            'region' => ['sometimes', 'nullable', 'string', Rule::exists('regions', 'ref')->where(
                function ($query) {
                    return $query->where("site_id",  $this->site?->id);
                })],
            'city' => ['sometimes', 'nullable', 'string', Rule::exists('cities', 'ref')->where(
                function ($query) {
                    return $query->where("site_id",  $this->site?->id);
                })],
            'venue' => ['sometimes', 'nullable', 'string', Rule::exists('venues', 'ref')->where(
                function ($query) {
                    return $query->where("site_id",  $this->site?->id);
                })],
            'experience' => ['sometimes', 'nullable', 'string', Rule::exists('experiences', 'ref')->where(
                function ($query) {
                    return $query->where("site_id",  $this->site?->id);
                })],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ];
    }
}
