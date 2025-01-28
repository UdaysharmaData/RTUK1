<?php

namespace App\Modules\Partner\Requests;

use Auth;
use Rule;
use App\Traits\SiteTrait;
use App\Modules\Partner\Models\Partner;
use Illuminate\Foundation\Http\FormRequest;
use App\Traits\FailedValidationResponseTrait;

class PartnerDeleteRequest extends FormRequest
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
            'refs' => ['required', 'array', Rule::exists('partners', 'ref')->where(
                function ($query) {
                    return $query->where("site_id", static::getSite()?->id);
                })],
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
                'description' => 'The refs of the partners. Can be a string or an array of partners refs. Must be one of '. implode(', ', Partner::inRandomOrder()
                    ->whereHas('site', function ($query) {
                        $query->where('id', static::getSite()?->id);
                    })->limit(3)->pluck('ref')->toArray()),
                'example' => Partner::inRandomOrder()
                    ->whereHas('site', function ($query) {
                        $query->where('id', static::getSite()?->id);
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
