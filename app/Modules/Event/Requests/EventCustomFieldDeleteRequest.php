<?php

namespace App\Modules\Event\Requests;

use Auth;
use Rule;
use App\Traits\SiteTrait;
use App\Modules\Event\Models\EventCustomField;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class EventCustomFieldDeleteRequest extends FormRequest
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
            'refs' => ['required', 'array', Rule::exists('event_custom_fields', 'ref')],
            'refs.*' => ['string']
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
            'refs' => [
                'description' => 'The refs of the event custom fields. Can be a string or an array of event custom fields refs. Must be one of '. implode(', ', EventCustomField::inRandomOrder()
                    ->whereHas('event', function ($query) {
                        $query->whereHas('eventCategories', function ($query) {
                            $query->whereHas('site', function ($query) {
                                $query->makingRequest();
                            });
                        });
                    })->limit(3)->pluck('ref')->toArray()),
                'example' => EventCustomField::inRandomOrder()
                    ->whereHas('event', function ($query) {
                        $query->whereHas('eventCategories', function ($query) {
                            $query->whereHas('site', function ($query) {
                                $query->makingRequest();
                            });
                        });
                    })->limit(3)->pluck('ref')->toArray()
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
            'refs' => $this->refs 
                ? (
                    is_array($this->refs) // Cast string to array if it's not an array
                        ? $this->refs
                        : collect($this->refs)->toArray()
                    )
                :
                null
        ]);
    }
}
