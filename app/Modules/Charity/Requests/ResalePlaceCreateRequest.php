<?php

namespace App\Modules\Charity\Requests;

use Auth;
use Rule;
use App\Traits\SiteTrait;
use App\Http\Helpers\AccountType;
use Illuminate\Http\JsonResponse;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ResalePlaceCreateRequest extends FormRequest
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
            'event' => ['required', 'string', Rule::exists('events', 'slug')],
            'charity' => ['required', 'string', Rule::exists('charities', 'slug')],
            'places' => ['required', 'numeric', 'integer'],
            'unit_price' => ['required', 'numeric', 'between:0,99999999999999.99'],
            'discount' => ['sometimes', 'nullable', 'boolean'],
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
            'event' => [
                'description' => 'The slug of the event. Must be one of '. implode(', ', Event::inRandomOrder()
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
                    })->first()?->slug
            ],
            'charity' => [
                'description' => 'The slug of the charity. Don\'t set this when authenticated as charity or charity_user. Must be one of '. implode(', ', Charity::inRandomOrder()->limit(6)->pluck('slug')->toArray()),
                'example' => Charity::inRandomOrder()->first()?->slug
            ],
            'places' => [
                'description' => 'The number of places given out',
                'example' => random_int(1, 500)
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
        $this->merge([ // Ensure only the admin can set the charity
            'charity' => AccountType::isCharityOwnerOrCharityUser()
                ? Auth::user()->charityUser->charity->slug
                : $this->charity
        ]);
    }
}
