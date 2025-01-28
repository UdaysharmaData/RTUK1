<?php

namespace App\Services\FileManager\Traits;

use App\Contracts\Uploadables\CanHaveUploadableResource;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use App\Services\FileManager\FileManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait SingleUploadModel
{
    /**
     * @throws \Exception
     */
    public function addFileToSingleUploadModel(
        CanHaveUploadableResource|Model $model,
        Request $request,
        string $fileKey,
        UploadTypeEnum $type,
        string $disk = 'local',
        string $fileVisibility = 'public',
        UploadUseAsEnum $as = UploadUseAsEnum::Image,
        string $related = 'upload'
    ): \Illuminate\Database\Eloquent\Model|null
    {
        if ($request->filled($fileKey)) {
            $uploadQuery = $model->$related();
            self::deleteExistingFile($uploadQuery, $fileVisibility);
            $url = $this->storeAndGetUrl($disk, $fileVisibility, $type, $request, $fileKey);

            if ($url && is_string($url)) {
                return $uploadQuery->create([
                    'url' => $url,
                    'type' => $type,
                    'use_as' => $as,
//                    'metadata' => FileManager::setFileMetadata($url)
                ]);
            } else throw new \Exception("An error occurred during your upload. Please try again in a bit.");
        }
        return null;
    }

    /**
     * @param string                                   $disk
     * @param string                                   $fileVisibility
     * @param UploadTypeEnum                           $type
     * @param array|\Illuminate\Http\UploadedFile|null $file
     * @return false|string|null
     * @throws \Exception
     */
    public static function uploadToDisk(string $disk, string $fileVisibility, UploadTypeEnum $type, array|\Illuminate\Http\UploadedFile|null $file): string|false|null
    {
        return Storage::disk($disk)->putFile(
            self::getPath($type),
            $file,
            $fileVisibility
        );
    }

    /**
     * @param  UploadTypeEnum                                                                    $type
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     * @throws \Exception
     */
    private static function getPath(UploadTypeEnum $type): mixed
    {
        return match ($type->value) {
            'image' => config('app.images_path'),
            'audio' => config('app.audios_path'),
            'csv' => config('app.csvs_path'),
            'pdf' => config('app.pdf_path'),
            default => throw new \Exception("Unknown file type [$type]"),
        };
    }

    /**
     * @param string         $disk
     * @param string         $fileVisibility
     * @param UploadTypeEnum $type
     * @param Request        $request
     * @param string         $fileKey
     * @return string|false|null
     * @throws \Exception
     */
    public static function storeAndGetUrl(string $disk, string $fileVisibility, UploadTypeEnum $type, Request $request, string $fileKey): string|false|null
    {
        if ($request->hasFile($fileKey)) {
            $file = $request->file($fileKey);
            return self::uploadToDisk($disk, $fileVisibility, $type, $file);
        } elseif (is_string($request->input($fileKey))) {
            $file = FileManager::createFileFromUrl($request->input($fileKey));
            return self::uploadToDisk($disk, $fileVisibility, $type, $file);
        }
        return null;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Relations\MorphOne $uploadQuery
     * @param string $fileVisibility
     * @return void
     */
    public static function deleteExistingFile(\Illuminate\Database\Eloquent\Relations\MorphOne $uploadQuery, string $fileVisibility): void
    {
        if (! is_null($uploads = $uploadQuery->get())) {
            foreach ($uploads as $upload) {
                if (isset($upload->url)) {
                    Storage::disk($fileVisibility)->delete($upload->url)
                    && $upload->delete();
                }
            }
        }
    }
}
