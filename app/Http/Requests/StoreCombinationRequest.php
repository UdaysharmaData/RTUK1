<?php

namespace App\Http\Requests;

use App\Traits\AddCombinationPathParameter;
use App\Traits\DraftCustomValidator;
use App\Traits\FailedValidationResponseTrait;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

use App\Traits\ImageValidator;
use App\Traits\GalleryValidator;
use App\Traits\FaqCustomValidator;
use App\Traits\MetaCustomValidator;

class StoreCombinationRequest extends FormRequest
{
    use MetaCustomValidator, FaqCustomValidator, FailedValidationResponseTrait, ImageValidator, GalleryValidator, DraftCustomValidator, AddCombinationPathParameter;

    /**
     * @var string[]
     */
    protected array $foreignKeys;

    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);

        // $this->foreignKeys = ['event_category_id', 'region_id', 'city_id', 'venue_id'];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', Rule::unique('combinations')->where(fn ($query) => $query->where('site_id', clientSiteId()))],
            'description' => ['nullable', 'string'],
            // 'event_category_id' => ['integer', Rule::exists('event_categories', 'id'), Rule::requiredIf($this->idsCheckerPasses())],
            // 'region_id' => ['integer', Rule::exists('regions', 'id'), Rule::requiredIf($this->idsCheckerPasses())],
            // 'city_id' => ['integer', Rule::exists('cities', 'id'), Rule::requiredIf($this->idsCheckerPasses())],
            // 'venue_id' => ['integer', Rule::exists('venues', 'id'), Rule::requiredIf($this->idsCheckerPasses())],
            // Image
            ...$this->imageRules(),
            // Gallery
            ...$this->galleryRules(),
            // Meta
            ...$this->metaRules(),
            // FAQs
            ...$this->faqCreateRules(),
            // Draft
            ...$this->draftRules(),
            'path' => [
                'sometimes',
                'string',
                'starts_with:/',
                Rule::unique('combinations')
                    ->where(fn ($query) => $query->where('site_id', clientSiteId()))
            ]
        ];
    }

    /**
     * @return void
     */
    protected function passedValidation(): void
    {
        if (isset($this->keywords)) {
            $this->replace(['keywords' => implode(',', $this->keywords)]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    // public function messages(): array
    // {
    //     $idsMessage = "Please select options for at least two (2) of these items; 'Category', 'Region', 'City', 'Venue'";
    //     $messages = [];

    //     foreach ($this->foreignKeys as $key => $value) {
    //         $messages[] = ["$value.required" => $idsMessage];
    //     }

    //     return array_merge(['path.starts_with' => 'Path must start with a forward slash (/)'], [
    //         ...$this->imageMessages(),
    //         ...$this->galleryMessages(),
    //         ...$this->metaMessages(),
    //         ...$this->faqMessages(),
    //         ...$this->draftMessages()
    //     ], ...$messages);
    // }

    /**
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Specifies combination name attribute.',
                'example' => 'FAQs Combination'
            ],
            'description' => [
                'description' => 'Specifies combination description attribute.',
                'example' => 'Some details'
            ],
            'event_category_id' => [
                'description' => 'Specifies an event category id.',
                'example' => '1'
            ],
            'region_id' => [
                'description' => 'Specifies a region id.',
                'example' => '2'
            ],
            'city_id' => [
                'description' => 'Specifies a city id.',
                'example' => '3'
            ],
            'venue_id' => [
                'description' => 'Specifies a venue id.',
                'example' => '4'
            ],
            ...$this->imageBodyParameters(),
            ...$this->galleryBodyParameters(),
            ...$this->metaBodyParameters(),
            ...$this->faqCreateBodyParameters(),
            ...$this->draftBodyParameters(),
            'path' => [
                'description' => 'Specifies combination path attribute.',
                'example' => '/path/to/combination'
            ]
        ];
    }

    /**
     * @return \Closure
     */
    private function idsCheckerPasses(): \Closure
    {
        return function () {
            $ids = array_filter(
                $this->only($this->foreignKeys)
            );

            return count($ids) < 2;
        };
    }
}
