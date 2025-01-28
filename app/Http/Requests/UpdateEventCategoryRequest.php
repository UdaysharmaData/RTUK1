<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Modules\Setting\Models\Site;
use Illuminate\Validation\Rules\Enum;
use App\Enums\EventCategoryVisibilityEnum;
use Illuminate\Foundation\Http\FormRequest;

use App\Traits\ImageValidator;
use App\Traits\GalleryValidator;
use App\Traits\FaqCustomValidator;
use App\Traits\MetaCustomValidator;
use App\Traits\FailedValidationResponseTrait;

class UpdateEventCategoryRequest extends FormRequest
{
    use MetaCustomValidator, FaqCustomValidator, FailedValidationResponseTrait, ImageValidator, GalleryValidator;

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
            'site_ref' => ['required_with:name', 'string', 'exists:sites,ref'],
            'name' => ['sometimes', 'string', Rule::unique("event_categories")->where(
                function ($query) {
                    return $query->where(
                        [
                            ["name", "=", $this->name],
                            ["site_id", "=", Site::where('ref', $this->site_ref)->first()?->id]
                        ]
                    );
                }
            )->ignore($this->route('category')?->id), function ($attribute, $value, $fail) { // Check if the user has access to the site (site_ref submitted)
                $hasAccess = Site::makingRequest()
                    ->hasAccess()
                    ->where('ref', $this->site_ref)
                    ->first();

                if (!$hasAccess) {
                    $fail('Sorry! You don\'t have access to the site submitted.');
                    \Log::channel('adminanddeveloper')->debug('Attempt to access a site the user does not have access to. Site ID: ' . $value . ' User ID: ' . auth()->id());
                }
            }],
            'slug' => ['sometimes', 'string', Rule::unique("event_categories")->where(
                function ($query) {
                    return $query->where(
                        [
                            ["slug", "=", $this->slug],
                            ["site_id", "=", Site::where('ref', $this->site_ref)->first()?->id]
                        ]
                    );
                }
            )->ignore($this->route('category')?->id), function ($attribute, $value, $fail) { // Check if the user has access to the site (site_ref submitted)
                $hasAccess = Site::makingRequest()
                    ->hasAccess()
                    ->where('ref', $this->site_ref)
                    ->first();

                if (!$hasAccess) {
                    $fail('Sorry! You don\'t have access to the site submitted.');
                    \Log::channel('adminanddeveloper')->debug('Attempt to access a site the user does not have access to. Site ID: ' . $value . ' User ID: ' . auth()->id());
                }
            }],
            'description' => ['nullable', 'string'],
            'color' => ['sometimes', 'required', 'string', 'max:16'],
            'distance_in_km' => ['sometimes', 'nullable', 'numeric', 'between:0,999999.9999'],
            'visibility' => ['sometimes', 'required', new Enum(EventCategoryVisibilityEnum::class)],
            // Meta
            ...$this->metaRules(),
            //FAQs
            ...$this->faqUpdateRules($this->route('category')),
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
            'site_ref' => [
                'description' => 'site ref',
                'example' => '97715e8d-ab6e-4e14-8eb3-c667d2d1e38b'
            ],
            'name' => [
                'description' => 'The name of the event category',
                'example' => 'Marathons'
            ],
            'description' => [
                'description' => 'The description of the event category',
                'example' => 'Description of the event category'
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
            ...$this->faqUpdateBodyParameters(),
            ...$this->imageBodyParameters(),
            ...$this->galleryBodyParameters()
        ];
    }
}
