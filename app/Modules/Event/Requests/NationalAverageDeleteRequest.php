<?php

namespace App\Modules\Event\Requests;

use Auth;
use Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Event\Models\NationalAverage;
use App\Traits\FailedValidationResponseTrait;

class NationalAverageDeleteRequest extends FormRequest
{
    use FailedValidationResponseTrait;

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
            'refs' => ['required', 'array', Rule::exists('national_averages', 'ref')],
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
                'description' => 'The refs of the national average(s). Can be a string or an array of national averages refs. Must be one of '. implode(', ', NationalAverage::inRandomOrder()
                    ->whereHas('eventCategory', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        });
                    })->limit(3)->pluck('ref')->toArray()),
                'example' => NationalAverage::inRandomOrder()
                    ->whereHas('eventCategory', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
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
