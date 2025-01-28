<?php

namespace App\Http\Requests;

use App\Models\City;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

use App\Traits\ImageValidator;
use App\Traits\GalleryValidator;
use App\Traits\FaqCustomValidator;
use App\Traits\MetaCustomValidator;
use App\Modules\Setting\Models\Site;
use App\Traits\FailedValidationResponseTrait;

class UpdateCityRequest extends FormRequest
{
    use FaqCustomValidator, FailedValidationResponseTrait, MetaCustomValidator, ImageValidator, GalleryValidator;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'site_id' => ['required_with:name,region_id', 'integer', 'exists:sites,id'],
            'name' => ['sometimes', 'required_with:site_id', 'string', function ($attribute, $value, $fail) { // Ensure the city does not exists for the site making the request
                $city = City::where('name', $this->name)
                    ->where('site_id', Site::whereId($this->site_id)->first()?->id)
                    ->where('id', '!=', $this->route('city')?->id);

                if ($city->exists()) {
                    return $fail('A city with that name already exists.');
                }

                if ($city->withTrashed()->exists()) {
                    return $fail('A [deleted] city with that name already exists. Either restore it or delete it permanently.');
                }
            }],
            'region_id' => ['bail', 'sometimes', 'required_with:site_id', 'integer', Rule::exists('regions', 'id')->where(
                function ($query) {
                    if ($this->site_id) {
                        $query->where('site_id', $this->site_id);
                    }
                })
            ],
            'description' => ['nullable', 'string'],
            //Meta
            ...$this->metaRules(),
            //FAQ
            ...$this->faqUpdateRules($this->route('city')),
            ...$this->imageRules(),
            ...$this->galleryRules()
        ];
    }

    /**
     * messages
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            ...$this->metaMessages(),
            ...$this->faqMessages(),
            ...$this->imageRules(),
            ...$this->galleryRules()
        ];
    }

    /**
     * bodyParameters
     *
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'site_id' => [
                'description' => 'The site id of the city',
                'example' => 1
            ],
            'region_id' => [
                'description' => 'the region id of the city',
                'example' => 1
            ],
            'name' => [
                'description' => 'The name of the city',
                'example' => 'city name'
            ],
            'description' => [
                'description' => 'The description of the city',
                'example' => 'city description'
            ],
            ...$this->metaBodyParameters(),
            ...$this->faqUpdateBodyParameters(),
            ...$this->imageBodyParameters(),
            ...$this->galleryBodyParameters()
        ];
    }
}
