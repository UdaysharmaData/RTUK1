<?php

namespace App\Traits;

use App\Enums\UploadTypeEnum;
use App\Rules\EnsureUploadDataExists;

trait ImageValidator
{
    use FailedValidationResponseTrait;

    /**
     * @param  bool  $required
     * @return array
     */
    public function imageRules(bool $required = false): array
    {
        if ($required) {
            $rules = [
                'image' => ['required', 'string', new EnsureUploadDataExists(UploadTypeEnum::Image)],
            ];
        } else {
            $rules = [
                'image' => ['sometimes', 'required', 'string', new EnsureUploadDataExists(UploadTypeEnum::Image)],
            ];
        }

        return $rules;
    }

    /**
     * @return string[]
     */
    public function imageMessages(): array
    {
        return  [
            'image.required' => 'The image is required.',
        ];
    }

    /**
     * @return array
     */
    public function imageBodyParameters(): array
    {
        return [
            'image' => [
                'title' => 'image ref',
                'example' => '97ad9df6-bc08-4729-b95e-3671dc6192c2'
            ]
        ];
    }
}
