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
use App\Traits\DraftCustomValidator;
use App\Traits\FailedValidationResponseTrait;

class StoreVenueRequest extends FormRequest
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
            'name' => ['bail', 'required_with:site_id', 'string', 'regex:/^[a-zA-Z0-9\s]+$/', function ($attribute, $value, $fail) { // Ensure the venue does not exists for the site making the request
                $venue = Venue::where('name', $this->name)
                    ->where('site_id', Site::whereId($this->site_id)->first()?->id);

                if ($venue->exists()) {
                    return $fail('A venue with that name already exists.');
                }

                if ($venue->withTrashed()->exists()) {
                    return $fail('A [deleted] venue with that name already exists. Either restore it or delete it permanently.');
                }
            }],
            'name' => [
                'bail', 'required_with:site_id', 'string', 'regex:/^[a-zA-Z0-9\s]+$/', Rule::unique("venues")->where(
                    function ($query) {
                        return $query->where(
                            [
                                ["name", "=", $this->name],
                                ["site_id", "=", Site::whereId($this->site_id)->first()?->id]
                            ]
                        );
                    }
                )
            ],
            'site_id' => ['bail', 'required', 'integer', 'exists:sites,id', function ($attribute, $value, $fail) { // Check if the user has access to the site (site_id submitted)
                $hasAccess = Site::makingRequest()
                    ->hasAccess()
                    ->whereId($value)
                    ->first();

                if (!$hasAccess) {
                    $fail('Sorry! You don\'t have access to the site submitted.');
                    \Log::channel('adminanddeveloper')->debug('Attempt to access a site the user does not have access to. Site ID: ' . $value . ' User ID: ' . auth()->id());
                }
            }],
            'city_id' => ['bail', 'required',  'integer', Rule::exists('cities', 'id')->where(
                function ($query) {
                    $query->where('site_id', $this->site_id);
                })
            ],
            'description' => ['nullable', 'string'],
            ...$this->metaRules(),
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
            'name' => [
                'description' => 'The name of the venue',
                'example' => 'venue'
            ],
            'site_id' => [
                'description' => 'The site id',
                'example' => 2
            ],
            'city_id' => [
                'description' => 'The city id of the venue',
                'example' => 2
            ],
            'description' => [
                'description' => 'The description of the venue.',
                'example' => 'Description of the venue'
            ],
            ...$this->metaBodyParameters(),
            ...$this->faqCreateBodyParameters(),
            ...$this->imageBodyParameters(),
            ...$this->galleryBodyParameters(),
            ...$this->draftBodyParameters()
        ];
    }
}
