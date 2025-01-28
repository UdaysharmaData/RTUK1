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
use App\Traits\DraftCustomValidator;
use App\Traits\FailedValidationResponseTrait;

class StoreCityRequest extends FormRequest
{
    use FaqCustomValidator, FailedValidationResponseTrait, MetaCustomValidator, ImageValidator, GalleryValidator, DraftCustomValidator;

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
             'name' => ['bail', 'required_with:site_id', 'string', 'regex:/^[a-zA-Z0-9\s]+$/', function ($attribute, $value, $fail) { // Ensure the city does not exists for the site making the request
                $city = City::where('name', $this->name)
                    ->where('site_id', Site::whereId($this->site_id)->first()?->id);

                if ($city->exists()) {
                    return $fail('A city with that name already exists.');
                }

                if ($city->withTrashed()->exists()) {
                    return $fail('A [deleted] city with that name already exists. Either restore it or delete it permanently.');
                }
            }],
            'site_id' => ['bail', 'required', 'integer', 'exists:sites,id', function ($attribute, $value, $fail) { // Check if the user has access to the site (site_id submitted)
                $hasAccess = Site::makingRequest()
                    ->hasAccess()
                    ->whereId($value)
                    ->first();

                if (!$hasAccess) {
                    $fail('Sorry! You don\'t have access to the site submitted.');
                    \Log::channel('adminanddeveloper')->debug('Attempt to access a site the user does not have access to. Site ID: ' . $value . ' User ID: ' . auth()->id());
                    // TODO: LOG a message to notify the developer's on slack
                }
            }],
            'region_id' => ['bail', 'required',  'integer', Rule::exists('regions', 'id')->where(
                function ($query) {
                    $query->where('site_id', $this->site_id);
                })
            ],
            'description' => ['nullable', 'string'],
            //Meta
            ...$this->metaRules(),
            //FAQ
            ...$this->faqCreateRules(),
            ...$this->imageRules(),
            ...$this->galleryRules(),
            ...$this->draftRules()
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
            ...$this->galleryMessages(),
            ...$this->draftMessages()
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
                'description' => 'The region id of the city',
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
            ...$this->faqCreateBodyParameters(),
            ...$this->imageBodyParameters(),
            ...$this->galleryBodyParameters(),
            ...$this->draftBodyParameters()
        ];
    }
}
