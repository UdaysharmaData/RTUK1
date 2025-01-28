<?php

namespace App\Services\FileManager;

use App\Enums\UploadImageSizeVariantEnum;
use App\Models\Upload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Enums\UploadTypeEnum;


class UploadFileManager
{
    private string $fileType;

    private string $fileName;

    private UploadedFile $file;

    private string $disk;

    private string $relativeDirectoryPath;

    public function __construct(?string $disk = null)
    {
        $this->disk = $disk ?: config('filesystems.default');
    }

    /**
     * Store files
     *
     * @param  mixed $media
     * @return void
     */
    public function storeFiles(array $media, $visibility = 'public')
    {
        $uploads = [];

        foreach ($media as $mediaData) {
            $uploads[] = $this->upload($mediaData['file'], $mediaData, $visibility);
        }

        return $uploads;
    }

    /**
     * updateFile
     *
     * @param  mixed $upload
     * @param  mixed $mediaData
     * @param  mixed $visibility
     * @return Upload
     */
    public function updateFile(Upload $upload, array $mediaData, $visibility = 'public'): Upload
    {
        if (isset($mediaData['file']) && $mediaData['file']) {
            $upload = $this->upload($mediaData['file'], $mediaData, $visibility, $upload);
        } else {
            if (isset($mediaData['device_versions']) && $mediaData['device_versions']) {
                $upload = $this->uploadImageDeviceVersions($mediaData['device_versions'], $upload, $visibility);
            }

            $upload = $this->createOrUpdateUploadRecord($mediaData, $upload);
        }

        return $upload;
    }

    /**
     * Upload file
     *
     * @param  mixed $file
     * @param  mixed $visibility 
     * 
     * @return Upload
     */
    public function upload(UploadedFile|string $file, array $mediaData, $visibility = 'public', ?Upload $upload = null): Upload
    {
        $this->setFile($file);

        if ($upload) {
            $this->fileName = basename($upload->url);
            $this->relativeDirectoryPath = dirname($upload->url);

            FileManager::deleteFile($upload->url);
        } else {
            $this->fileName = $this->file->hashName();
            $this->relativeDirectoryPath = $this->relativeDirectoryPath();
        }

        Storage::disk($this->disk)->putFileAs(
            $this->relativeDirectoryPath,
            $this->file,
            $this->fileName,
            $visibility
        );

        $upload =  $this->createOrUpdateUploadRecord($mediaData, $upload);

        if (isset($mediaData['device_versions']) && $mediaData['device_versions']) {
            $upload = $this->uploadImageDeviceVersions($mediaData['device_versions'], $upload, $visibility);
        }

        return $upload;
    }

    /**
     * Upload image device versions
     *
     * @param  array $deviceVersions
     * @param  Upload $upload
     * @param  mixed $visibility
     * @return Upload
     */
    public function uploadImageDeviceVersions(array $deviceVersions, Upload $upload, $visibility = 'public'): Upload
    {
        if ($upload->type == UploadTypeEnum::Image && $deviceVersions) {
            foreach ($deviceVersions as $device => $file) {
                $imageSize = UploadImageSizeVariantEnum::options();

                if (!in_array($device, $imageSize)) { // check if the device is a valid image size variant
                    continue;
                }

                if (isset($upload->device_versions[$device]) && $upload->device_versions[$device]) {
                    FileManager::deleteFile($upload->device_versions[$device]);
                }

                Storage::disk($this->disk)->putFileAs(
                    dirname($upload->url),
                    $this->getUploadedFile($file),
                    str_replace('.', "_{$device}.", basename($upload->url)),
                    $visibility
                );

                $upload->update(['resized' => true]);
            }
        }

        return $upload;
    }

    /**
     * Set file properties
     *
     * @param  mixed $file
     * @return void
     */
    private function setFile(UploadedFile|string $file)
    {
        $this->file = $this->getUploadedFile($file);
        $this->fileType = FileManager::guessFileType($this->file);
    }


    /**
     *  Create or update upload record in database
     *
     * @param  mixed $mediaData
     * @return Upload
     */
    private function createOrUpdateUploadRecord(array $mediaData, ?Upload $upload = null): Upload
    {
        if (!$upload) {
            $upload = new Upload();
            $upload->type = $this->fileType;
            $upload->url = $this->getFilePath();
        }

        $upload->title = $mediaData['title'] ?? null;
        $upload->caption = $mediaData['caption'] ?? null;
        $upload->description = $mediaData['description'] ?? null;
        $upload->alt = $mediaData['alt'] ?? null;
        $upload->private = $mediaData['private'] ?? false;
        $upload->save();

        return $upload;
    }

    /**
     * Get relative directory path
     *
     * @return void
     */

     private function relativeDirectoryPath()
     {
         $path = FileManager::getPath(UploadTypeEnum::from($this->fileType));
         if ($path === null) {
             $path = '/uploads/public/media/pdf';
         }
         if ($this->fileType == UploadTypeEnum::Image->value) {
             $folder = pathinfo($this->fileName, PATHINFO_FILENAME);
             $path = $path . '/' . $folder;
         }
         return $path;
     }
     
    // private function relativeDirectoryPath()
    // {
    //     $path = FileManager::getPath(UploadTypeEnum::from($this->fileType));

    //     if ($this->fileType == UploadTypeEnum::Image->value) {
    //         $folder = pathinfo($this->fileName, PATHINFO_FILENAME);
    //         $path = $path . '/' . $folder;
    //     }

    //     return $path;
    // }

    /**
     * Get uploaded file
     *
     * @param  mixed $file
     * @return UploadedFile
     */
    private function getUploadedFile(UploadedFile|string $file): UploadedFile
    {
        if ($file instanceof UploadedFile) {
            return $file;
        }

        return FileManager::createFileFromUrl($file);
    }

    /**
     * Get file path
     *
     * @return string
     */
    private function getFilePath(): string
    {
        return $this->relativeDirectoryPath . '/' . $this->fileName;
    }
}
