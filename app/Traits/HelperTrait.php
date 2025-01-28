<?php

namespace App\Traits;

use App\Contracts\Uploadables\CanHaveUploadableResource;
use App\Models\Meta;
use Illuminate\Http\Request;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use App\Models\Upload;
use App\Modules\Event\Models\Event;
use Illuminate\Support\Facades\Storage;

trait HelperTrait
{
    use UploadTrait;

    /**
     * Save the model's uploads (images, pdf etc) (for models implementing the CanHaveManyUploadableResource)
     *
     * @param  array          $uploadedFiles
     * @param  object           $model
     * @param  UploadTypeEnum   $type
     * @param  UploadUseAsEnum  $useAs
     * @return void
     */
    protected function saveUploads(array $uploadedFiles, object $model, UploadTypeEnum $type = UploadTypeEnum::Image, UploadUseAsEnum $useAs = UploadUseAsEnum::Image)
    {
        $path = static::getPath($type);

        foreach ($uploadedFiles as $file) { // Upload and save the new file
            // if ($image->isValid()) {
            if ($filePath = $this->moveUploadedFile($file, $path, $useAs)) {
                $model->uploads()->create([
                    'site_id' => clientSiteId(),
                    'title' => $model->name,
                    'url' => $filePath,
                    'type' => $type,
                    'use_as' => $useAs
                ]);
            }
            // }
        }
    }

    protected function storeUploadable(CanHaveUploadableResource $model, string $uploadRef, UploadUseAsEnum $useAs = UploadUseAsEnum::Image)
    {
        if ($upload = Upload::where('ref', $uploadRef)->first()) {
            $model->uploadable()->updateOrCreate([
                'upload_id' => $upload->id,
                'use_as' => $useAs
            ]);
        }
    }

    /**
     * Save the model's image (for models implementing the CanHaveUploadableResource)
     * // TODO: Update this first param from Request $request, to $request->{property} where property can be image.
     *
     * @param  Request          $request
     * @param  object           $model
     * @param  UploadTypeEnum   $type
     * @param  UploadUseAsEnum  $useAs
     * @return void
     */
    protected function saveUpload(Request $request, object $model, UploadTypeEnum $type = UploadTypeEnum::Image, UploadUseAsEnum $useAs = UploadUseAsEnum::Image)
    {
        $upload = $model->upload;

        if ($upload && Storage::disk(config('filesystems.default'))->exists($upload->url)) { // Delete the existing upload if it exists
            Storage::disk(config('filesystems.default'))->delete($upload->url);
        }

        $path = static::getPath($type);

        if ($filePath = $this->moveUploadedFile($request->image['image'], $path, $useAs)) {
            $model->upload()->updateOrCreate([], [
                'site_id' => clientSiteId(),
                'url' => $filePath,
                'title' => $request->image['title'] ?? $model->name,
                'caption' => $request->image['caption'] ?? null,
                'alt' => $request->image['alt'] ?? null,
                'type' => $type,
                'use_as' => $useAs
            ]);
        }
    }

    /**
     * Save the model's image [event, event category, region] (for models implementing the CanHaveManyUploadableResource)
     *
     * @param  Request  $request
     * @param  object   $model
     * @return void
     */
    protected function saveImage(Request $request, object $model)
    {
        $image = $model->image;

        if ($image && Storage::disk(config('filesystems.default'))->exists($image->url)) { // Delete the existing image if it exists
            Storage::disk(config('filesystems.default'))->delete($image->url);
        }

        $path = static::getPath(UploadTypeEnum::Image);

        if ($filePath = $this->moveUploadedFile($request->image['image'], $path, UploadUseAsEnum::Image)) {
            $model->image()->updateOrCreate([], [
                'site_id' => clientSiteId(),
                'url' => $filePath,
                'title' => $request->image['title'] ?? $model->name,
                'caption' => $request->image['caption'] ?? null,
                'alt' => $request->image['alt'] ?? null,
                'type' => UploadTypeEnum::Image,
                'use_as' => UploadUseAsEnum::Image
            ]);
        }
    }

    /**
     * Save the model's logo [charity, partner]
     *
     * @param  Request  $request
     * @param  object   $model
     * @return void
     */
    protected function saveLogo(Request $request, object $model)
    {
        $logo = $model->logo;

        if ($logo && Storage::disk(config('filesystems.default'))->exists($logo->url)) { // Delete the existing logo if it exists
            Storage::disk(config('filesystems.default'))->delete($logo->url);
        }

        $path = static::getPath(UploadTypeEnum::Image);

        if ($filePath = $this->moveUploadedFile($request->image['logo'], $path, UploadUseAsEnum::Logo)) {
            $model->logo()->updateOrCreate([], [
                'site_id' => clientSiteId(),
                'title' => $request->image['title'] ?? $model->name,
                'caption' => $request->image['caption'] ?? null,
                'alt' => $request->image['alt'] ?? null,
                'url' => $filePath,
                'type' => UploadTypeEnum::Image,
                'use_as' => UploadUseAsEnum::Logo
            ]);
        }
    }

    /**
     * Save the model's gallery [event]
     *
     * @param  Request  $request
     * @param  object   $model
     * @return void
     */
    protected function saveGallery(Request $request, object $model)
    {
        $path = static::getPath(UploadTypeEnum::Image);

        foreach ($request->gallery as $image) { // Upload and save the new gallery
            // if ($image->isValid()) {
            if ($filePath = $this->moveUploadedFile($image['image'], $path, UploadUseAsEnum::Gallery)) {
                $model->gallery()->create([
                    'site_id' => clientSiteId(),
                    'title' => $image['title'] ?? $model->name,
                    'caption' => $image['caption'] ?? null,
                    'alt' => $image['alt'] ?? null,
                    'url' => $filePath,
                    'type' => UploadTypeEnum::Image,
                    'use_as' => UploadUseAsEnum::Gallery
                ]);
            }
            // }
        }
    }

    /**
     * Save the model's images [charity]
     *
     * @param  Request  $request
     * @param  object   $model
     * @return void
     */
    protected function saveImages(Request $request, object $model)
    {
        $path = config('app.images_path');

        foreach ($request->images as $image) { // Upload and save the new images
            // if ($image->isValid()) {
            if ($filePath = $this->moveUploadedFile($image['image'], $path, UploadUseAsEnum::Images)) {
                $model->images()->create([
                    'site_id' => clientSiteId(),
                    'title' => $image['title'] ?? $model->name,
                    'caption' => $image['caption'] ?? null,
                    'alt' => $image['alt'] ?? null,
                    'url' => $filePath,
                    'type' => UploadTypeEnum::Image,
                    'use_as' => UploadUseAsEnum::Images
                ]);
            }
            // }
        }
    }

    /**
     * Save the event route_info media
     *
     * @param  Request  $request
     * @param  Event    $event
     * @return void
     */
    protected function saveRouteInfoMedia(Request $request, Event $event)
    {
        $path = config('app.images_path');

        foreach ($request->route_info['media'] as $image) { // Upload and save the new files
            // if ($image->isValid()) {
            if ($filePath = $this->moveUploadedFile($image, $path, UploadUseAsEnum::RouteInfo)) {
                $event->routeInfoMedia()->create([
                    'site_id' => clientSiteId(),
                    'title' => $event->name . ' | route information',
                    'url' => $filePath,
                    'type' => UploadTypeEnum::Image,
                    'use_as' => UploadUseAsEnum::RouteInfo
                ]);
            }
            // }
        }
    }

    /**
     * Save the event what_is_included media
     *
     * @param  Request  $request
     * @param  Event    $event
     * @return void
     */
    protected function saveWhatIsIncludedMedia(Request $request, Event $event)
    {
        $path = config('app.images_path');

        foreach ($request->what_is_included['media'] as $image) { // Upload and save the new files
            // if ($image->isValid()) {
            if ($filePath = $this->moveUploadedFile($image, $path, UploadUseAsEnum::WhatIsIncluded)) {
                $event->whatIsIncludedMedia()->create([
                    'site_id' => clientSiteId(),
                    'title' => $event->name . ' | what is included',
                    'url' => $filePath,
                    'type' => UploadTypeEnum::Image,
                    'use_as' => UploadUseAsEnum::WhatIsIncluded
                ]);
            }
            // }
        }
    }

    /**
     * Save the model's socials.
     *
     * @param  Request  $request
     * @param  object   $model
     * @return void
     */
    protected function saveSocials(Request $request, object $model)
    {
        foreach ($request->socials as $social) {
            $model->socials()->updateOrCreate([
                'platform' => $social['platform'],
            ], [
                'url' => $social['url']
            ]);
        }
    }

    /**
     * Save the model meta data.
     *
     * @param  Request    $request
     * @param  object     $model
     * @return Meta|null
     */
    protected function saveMetaData(Request $request, object $model): ?Meta
    {
        if ($request->filled('meta')) {
            $meta = $request->meta;

            if (empty(array_filter($meta))) {
                $model->meta()->delete();
            } else {
                return $model->meta()->updateOrCreate([], $meta);
            }
        }

        return null;
    }

    /**
     * Get the path to save the file
     *
     * @param  UploadTypeEnum  $type
     * @return string
     */
    protected static function getPath(UploadTypeEnum $type): string
    {
        switch ($type) {
            case UploadTypeEnum::Image:
                $value = config('app.images_path');
                break;

            case UploadTypeEnum::Audio:
                $value = config('app.audios_path');
                break;

            case UploadTypeEnum::CSV:
                $value = config('app.csvs_path');
                break;

            case UploadTypeEnum::PDF:
                $value = config('app.pdfs_path');
                break;

            case UploadTypeEnum::Video:
                $value = config('app.media_path');
                break;

            default:
                $value = config('app.images_path');
        }

        return $value;
    }
}
