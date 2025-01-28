<?php

namespace App\Traits;

use App\Enums\UploadTypeEnum;
use App\Rules\EnsureUploadDataExists;

trait GalleryValidator
{
    use FailedValidationResponseTrait;

    /**
     * @param  bool  $required
     * @param  int   $maxFileSize
     * @return array
     */
    public function galleryRules(bool $required = false): array
    {
        if ($required) {
            $rules = [
                'gallery' => ['required', 'array'],
            ];
        } else {
            $rules = [
                'gallery' => ['sometimes', 'required', 'array'],
            ];
        }

        return array_merge($rules, [
            'gallery.*' => ['required', 'string', new EnsureUploadDataExists(UploadTypeEnum::Image)],
        ]);
    }

    /**
     * @param  int       $maxFileSize
     * @return string[]
     */
    public function galleryMessages(): array
    {
        return  [
            'gallery.required' => 'The gallery is required.',
        ];
    }

    /**
     *
     * @return array
     */
    public function galleryBodyParameters(): array
    {
        return [
            "gallery" => [
                "title" => "An array of image ref",
                "example" => ['97ad9df6-bc08-4729-b95e-3671dc6192c2', '97ad9df6-bc08-4729-b95e-3671dc6192c2']
            ]
        ];
    }
}
