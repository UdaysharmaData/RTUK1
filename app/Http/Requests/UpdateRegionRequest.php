<?php

namespace App\Http\Requests;

use App\Models\Region;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

use App\Traits\ImageValidator;
use App\Traits\GalleryValidator;
use App\Traits\FaqCustomValidator;
use App\Traits\MetaCustomValidator;
use App\Modules\Setting\Models\Site;
use App\Traits\FailedValidationResponseTrait;

class UpdateRegionRequest extends FormRequest
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
            'site_id' => ['required_with:name', 'integer', 'exists:sites,id'],
            'name' => ['sometimes', 'required_with:site_id', 'string', function ($attribute, $value, $fail) { // Ensure the region does not exists for the site making the request
                $region = Region::where('name', $this->name)
                    ->where('site_id', Site::whereId($this->site_id)->first()?->id)
                    ->where('id', '!=', $this->route('region')?->id);

                if ($region->exists()) {
                    return $fail('A region with that name already exists.');
                }

                if ($region->withTrashed()->exists()) {
                    return $fail('A [deleted] region with that name already exists. Either restore it or delete it permanently.');
                }
            }],
            'country' => ['nullable', 'required', 'string'],
            'description' => ['nullable', 'string'],
            //Meta
            ...$this->metaRules(),
            //FAQ
            ...$this->faqUpdateRules($this->route('region')),
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
                'description' => 'The site id of the region',
                'example' => 2
            ],
            'name' => [
                'description' => 'The name of the region',
                'example' => 'region name'
            ],
            'country' => [
                'description' => 'The country of the region',
                'example' => 'region country'
            ],
            'description' => [
                'description' => 'The description of the region',
                'example' => 'region description'
            ],
            ...$this->metaBodyParameters(),
            ...$this->faqUpdateBodyParameters(),
            ...$this->imageBodyParameters(),
            ...$this->galleryBodyParameters()
        ];
    }
}
