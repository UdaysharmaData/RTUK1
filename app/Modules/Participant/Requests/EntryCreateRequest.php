<?php

namespace App\Modules\Participant\Requests;

use Auth;
use Rule;
use App\Traits\SiteTrait;
use App\Rules\IsActiveEvent;
use App\Modules\Charity\Models\Charity;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;
use App\Modules\Event\Models\EventEventCategory;

class EntryCreateRequest extends FormRequest
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
            'eec' => ['required'],
            'eec.*.ref' => ['bail', 'required', Rule::exists('event_event_category', 'ref'), function ($attribute, $value, $fail) { // Ensure the event submitted through the url is the same as the one to which the eec belong
                if (EventEventCategory::where('ref', $value)
                    ->whereHas('event', function ($query) {
                        $query->where('ref', $this->event?->ref);
                    })->whereHas('eventCategory', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        });
                    })->doesntExist()) {
                    $fail('The event category does not belong to the event. Please refresh your browser and try again.');
                }
            }, new IsActiveEvent],
            'eec.*.quantity' => [
                'required',
                'integer',
                // 'numeric'
            ],
            'eec.*.charity' => [
                'sometimes',
                'required',
                Rule::exists('charities', 'ref')
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
            'eec.*.required' => 'The selected event and category are required.',
            'eec.*.ref.required' => 'The eec ref is required.',
            'eec.*.ref.exists' => 'The selected event and category are invalid.'
        ];
    }

    public function bodyParameters()
    {
        return [
            'eec.*.charity' => [
                'description' => 'The ref of the charity. Must be one of '. Charity::where('name', 'WWF')->first()?->ref,
                'example' => Charity::where('name', 'WWF')->first()?->ref
            ],
            'eec.*.ref' => [
                'description' => 'The ref of the event event category. Must be one of '.implode(', ', EventEventCategory::inRandomOrder()
                    ->whereHas('eventCategory', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        });
                    })->limit(3)->pluck('ref')->toArray()),
                'example' => EventEventCategory::inRandomOrder()
                    ->whereHas('eventCategory', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        });
                    })->first()?->ref
            ]
        ];
    }
}
