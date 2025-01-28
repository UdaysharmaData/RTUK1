<?php

namespace App\Traits;

use Illuminate\Support\Str;
use App\Enums\UploadUseAsEnum;
use App\Models\Upload;
use Illuminate\Support\Facades\Storage;
use App\Services\FileManager\FileManager;

trait UploadTrait
{
    /**
     * Decode base64 string and save to storage (disk).
     * 
     * @param  string        $file
     * @param  string|null   $path
     * @param  string        $useAs
     * @return null|string
     */
    protected function moveUploadedFile(string $file, ?string $path = null, UploadUseAsEnum $useAs = UploadUseAsEnum::Image): null|string
    {
        $path = $path ?? config('app.images_path');

        $_file = FileManager::createFileFromUrl($file);

        return Storage::disk(config('filesystems.default'))->putFile($path, $_file, $this->getVisibility($useAs));
    }

    /**
     * Get file visibility from use_as enum.
     * 
     * @param UploadUseAsEnum $useAs
     */
    public static function getVisibility(UploadUseAsEnum $useAs): string
    {
        if (in_array($useAs, [UploadUseAsEnum::Avatar, UploadUseAsEnum::ProfileBackgroundImage, UploadUseAsEnum::PDF])) {
            return 'private';
        }

        return 'public';
    }
}