<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Modules\Setting\Models\Site;
use Illuminate\Validation\Rules\Enum;
use App\Enums\EventCategoryVisibilityEnum;
use App\Traits\DraftCustomValidator;
use Illuminate\Foundation\Http\FormRequest;

use App\Traits\ImageValidator;
use App\Traits\GalleryValidator;
use App\Traits\FaqCustomValidator;
use App\Traits\MetaCustomValidator;
use App\Traits\FailedValidationResponseTrait;

class StoreEventCategoryRequest extends FormRequest
{
    use MetaCustomValidator, FaqCustomValidator, FailedValidationResponseTrait, ImageValidator, GalleryValidator, DraftCustomValidator;

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
            'name' => ['bail', 'required_with:site_ref', 'string', Rule::unique("event_categories")->where(
                function ($query) {
                    return $query->where(
                        [
                            ["name", "=", $this->name],
                            ["site_id", "=", Site::where('ref', $this->site_ref)->first()?->id]
                        ]
                    );
                }
            )],
            'slug' => ['bail', 'required_with:site_ref', 'string', Rule::unique("event_categories")->where(
                function ($query) {
                    return $query->where(
                        [
                            ["slug", "=", $this->slug],
                            ["site_id", "=", Site::where('ref', $this->site_ref)->first()?->id]
                        ]
                    );
                }
            )],
            'site_ref' => ['bail', 'required', 'string', 'exists:sites,ref', function ($attribute, $value, $fail) { // Check if the user has access to the site (site_ref submitted)
                $hasAccess = Site::makingRequest()
                    ->hasAccess()
                    ->where('ref', $value)
                    ->first();

                if (!$hasAccess) {
                    $fail('Sorry! You don\'t have access to the site submitted.');
                    \Log::channel('adminanddeveloper')->debug('Attempt to access a site the user does not have access to. Site ID: ' . $value . ' User ID: ' . auth()->id());
                }
            }],
            'description' => ['nullable', 'string'],
            'color' => ['required', 'string', 'max:16'],
            'distance_in_km' => ['nullable', 'numeric', 'between:0,999999.9999'],
            'visibility' => ['required', new Enum(EventCategoryVisibilityEnum::class)],
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
                'description' => 'The name of the event category',
                'example' => 'Marathons'
            ],
            'site_ref' => [
                'description' => 'site ref',
                'example' => '97715e8d-ab6e-4e14-8eb3-c667d2d1e38b'
            ],
            'color' => [
                'description' => 'Event Category color',
                'example' => '#f0ad00'
            ],
            'distance_in_km' => [
                'description' => 'Event Category distance',
                'example' => '42.195'
            ],
            'visibility' => [
                'description' => 'The visibility. Must be one of public, private',
                'example' => 'public'
            ],
            ...$this->metaBodyParameters(),
            ...$this->faqCreateBodyParameters(),
            ...$this->imageBodyParameters(),
            ...$this->galleryBodyParameters(),
            ...$this->draftBodyParameters()
        ];
    }
}
