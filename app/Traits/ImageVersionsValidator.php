<?php

namespace App\Traits;

use App\Enums\UploadImageSizeVariantEnum;
use Illuminate\Validation\Rules\Enum;


trait ImageVersionsValidator
{
    use FailedValidationResponseTrait;

    /**
     * @param  bool  $required
     * @return array
     */
    public function imageVersionsRules(): array
    {
        return [
            'image_versions' => ['sometimes', 'array'],
            'image_versions.*' => ['required', 'string',  new Enum(UploadImageSizeVariantEnum::class)]
        ];
    }

    /**
     * @return string[]
     */
    public function imageVersionsMessages(): array
    {
        return  [
            'image_versions.*.in' => 'The image version must be one of the following: '.implode(', ', UploadImageSizeVariantEnum::options())
        ];
    }

    /**
     * @return array
     */
    public function imageVersionsBodyParameters(): array
    {
        return [
            'image_versions' => [
                'title' => 'image versions',
                'example' => implode(', ', UploadImageSizeVariantEnum::options())
            ]
        ];
    }
}
