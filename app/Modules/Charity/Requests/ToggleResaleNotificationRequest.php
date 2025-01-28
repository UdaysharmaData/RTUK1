<?php

namespace App\Modules\Charity\Requests;

use Auth;
use Rule;
use App\Traits\SiteTrait;
use Illuminate\Http\JsonResponse;
use App\Modules\Event\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ToggleResaleNotificationRequest extends FormRequest
{
    use SiteTrait;

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
            'events' => ['required', 'array', Rule::exists('events', 'slug')],
            'status' => ['required', 'boolean']
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [

        ];
    }

    public function bodyParameters()
    {
        return [
            'events' => [
                'description' => 'The slug of the event(s). Can be a string or an array of event slugs. Must be one of '. implode(', ', Event::inRandomOrder()
                    ->whereHas('eventCategories', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        });
                    })->limit(3)->pluck('slug')->toArray()),
                'example' => Event::inRandomOrder()
                    ->whereHas('eventCategories', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        });
                    })->limit(3)->pluck('slug')->toArray(),
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
        $this->merge([
            'events' => $this->events 
                ? (
                    is_array($this->events) // Cast string to array if it's not an array
                        ? $this->events
                        : collect($this->events)->toArray()
                    )
                :
                null
        ]);
    }
}
