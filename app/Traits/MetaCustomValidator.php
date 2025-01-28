<?php

namespace App\Traits;

use App\Enums\MetaRobotsEnum;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

trait MetaCustomValidator
{
    use FailedValidationResponseTrait;

    /**
     * @return array
     */
    public function metaRules(): array
    {
        return [
            'meta' => ['sometimes', 'required', 'array:title,description,keywords,robots,canonical_url'],
            'meta.title' => [
                'nullable',
                Rule::requiredIf($this->meta && !(empty($this->meta['description'] ?? null) && empty($this->meta['keywords'] ?? null))),
                'string'
            ],
            'meta.description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'meta.keywords' => [
                'nullable',
                Rule::requiredIf($this->meta && !(empty($this->meta['description'] ?? null) && empty($this->meta['title'] ?? null))),
                'array'
            ],
            'meta.keywords.*' => ['required', 'string'],
            'meta.robots' => [
                'nullable',
                // Rule::requiredIf($this->meta && !(empty($this->meta['description'] ?? null) && empty($this->meta['title'] ?? null))),
                'array',
                function ($attribute, $value, $fail) {
                    if (count($value) > 2) {
                        $fail('The robots field may not have more than 2 items.');
                    }
                }
            ],
            'meta.robots.*' => [
                'required',
                'string',
                new Enum(MetaRobotsEnum::class)
            ],
            'meta.canonical_url' => [
                'nullable',
                'string',
                'active_url',
                'max:255',
            ]
        ];
    }

    /**
     * @return string[]
     */
    public function metaMessages(): array
    {
        return  [
            // 'meta.keywords.*' => 'Invalid keyword(s) provided.'
            'meta.title.required' => 'The title field is required when the description or the keywords is provided.',
            'meta.title.string' => 'The title must be a string.',
            'meta.description.required' => 'The description field is required.',
            'meta.description.string' => 'The description must be a string.',
            'meta.keywords.required' => 'The keywords field is required when the description or the title is provided.',
            'meta.keywords.string' => 'The keywords must be a string.'
        ];
    }

    /**
     *
     * @return array
     */
    public function metaBodyParameters(): array
    {
        return [
            'meta.title' => [
                'description' => 'Specifies title metadata.',
                'example' => 'Title'
            ],
            'meta.description' => [
                'description' => 'Specifies description metadata for combination.',
                'example' => 'Some description.'
            ],
            'meta.keywords.*' => [
                'description' => 'Specifies an array of keywords metadata for combination.',
                'example' => 'tag'
            ],
            'meta.canonical_url' => [
                'description' => 'The canonical url.',
                'example' => 'https://example.com'
            ],
        ];
    }
}
