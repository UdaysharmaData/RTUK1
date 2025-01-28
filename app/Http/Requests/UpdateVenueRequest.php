<?php

namespace App\Http\Requests;

use App\Models\Venue;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

use App\Traits\ImageValidator;
use App\Traits\GalleryValidator;
use App\Traits\FaqCustomValidator;
use App\Traits\MetaCustomValidator;
use App\Modules\Setting\Models\Site;
use App\Traits\FailedValidationResponseTrait;

class UpdateVenueRequest extends FormRequest
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
            'site_id' => ['required_with:name,city_id', 'integer', 'exists:sites,id'],
            'name' => ['sometimes', 'required_with:site_id', 'string', function ($attribute, $value, $fail) { // Ensure the venue does not exists for the site making the request
                $venue = Venue::where('name', $this->name)
                    ->where('site_id', Site::whereId($this->site_id)->first()?->id)
                    ->where('id', '!=', $this->route('venue')?->id);

                if ($venue->exists()) {
                    return $fail('A venue with that name already exists.');
                }

                if ($venue->withTrashed()->exists()) {
                    return $fail('A [deleted] venue with that name already exists. Either restore it or delete it permanently.');
                }
            }],
            'city_id' => ['sometimes', 'required_with:site_id', 'integer', Rule::exists('cities', 'id')->where(
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
            ...$this->faqUpdateRules($this->route('venue')),
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
            ...$this->imageMessages(),
            ...$this->galleryMessages()
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
                'description' => 'The site id of the venue',
                'example' => 1
            ],
            'city_id' => [
                'description' => 'The city id of the venue',
                'example' => 1
            ],
            'name' => [
                'description' => 'The name of the venue',
                'example' => 'venue name'
            ],
            'description' => [
                'description' => 'The description of the venue',
                'example' => 'venue description'
            ],
            ...$this->metaBodyParameters(),
            ...$this->faqUpdateBodyParameters(),
            ...$this->imageBodyParameters(),
            ...$this->galleryBodyParameters()
        ];
    }
}
