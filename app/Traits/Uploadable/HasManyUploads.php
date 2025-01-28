<?php

namespace App\Traits\Uploadable;

use Storage;
use App\Models\Upload;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use App\Models\Uploadable;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\Event;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasManyUploads
{
    /**
     * Get the media.
     * Contains the image/logo + gallery/images + video
     *
     * @return array
     */
    public function media(): array
    {
        $data = [];

        if ($this->uploads()->exists()) {
            if (static::class == Event::class) { // Get the event media
                if ($image = $this->image()->first()) { // Add the image to the media
                    $data[] = $image;
                }

                $data = array_merge($data, $this->gallery()->get()->toArray());

                if (isset($this->video)) { // Add the video to the media
                    $upload = new Upload();
                    $upload->fill([
                        'site_id' => null,
                        'use_as' => UploadUseAsEnum::Gallery,
                        'url' => $this->video,
                        'metadata' => null,
                        'title' => null,
                        'description' => null
                    ]);
                    $upload->type = UploadTypeEnum::Video;

                    $data[] = $upload;
                }
            } elseif (static::class == Charity::class) {

                if ($logo = $this->logo()->first()) { // Add the image to the media
                    $data[] = $logo;
                }

                // Add the images to the media
                $data = array_merge($data, $this->images()->get()->toArray());

                if (isset($this->video)) { // Add the video to the media
                    $upload = new Upload();
                    $upload->fill([
                        'site_id' => null,
                        'use_as' => UploadUseAsEnum::Images,
                        'url' => $this->video,
                        'metadata' => null,
                        'title' => null,
                        'description' => null
                    ]);
                    $upload->type = UploadTypeEnum::Video;

                    $data[] = $upload;
                }
            }
        }

        return $data;
    }

    /**
     * uploadables
     *
     * @return MorphMany
     */
    public function uploadables(): MorphMany
    {
        return $this->morphMany(Uploadable::class, 'uploadable');
    }

    /**
     * uploadable
     *
     * @return MorphOne
     */
    public function uploadable(): MorphOne
    {
        return $this->morphOne(Uploadable::class, 'uploadable');
    }

    /**
     * @return HasManyThrough
     */
    public function uploads(): HasManyThrough
    {
        return $this->hasManyThrough(
            Upload::class,
            Uploadable::class,
            'uploadable_id',
            'id',
            'id',
            'upload_id'
        )->where('uploadables.uploadable_type', self::class);
    }

    /**
     * get upload
     *
     * @return HasOneThrough
     */
    public function upload(): HasOneThrough
    {
        return $this->hasOneThrough(
            Upload::class,
            Uploadable::class,
            'uploadable_id',
            'id',
            'id',
            'upload_id'
        )->where('uploadables.uploadable_type', self::class);
    }

    /**
     * Get the logo.
     *
     * @return HasOneThrough
     */
    public function logo(): HasOneThrough
    {
        return $this->upload()->where('use_as', UploadUseAsEnum::Logo)->latest('uploads.id');
    }

    /**
     * Get the image.
     *
     * @return  HasOneThrough
     */
    public function image(): HasOneThrough
    {
        return $this->upload()->where('use_as', UploadUseAsEnum::Image)->latest('uploads.id');
    }

    /**
     * Get the images.
     *
     * @return HasManyThrough
     */
    public function images(): HasManyThrough
    {
        return $this->uploads()->where('use_as', UploadUseAsEnum::Images);
    }

    /**
     * Get the gallery.
     *
     * @return HasManyThrough
     */
    public function gallery(): HasManyThrough
    {
        return $this->uploads()->where('use_as', UploadUseAsEnum::Gallery);
    }

    /**
     * Get the route information media.
     *
     * @return HasManyThrough
     */
    public function routeInfoMedia(): HasManyThrough
    {
        return $this->uploads()->where('use_as', UploadUseAsEnum::RouteInfo);
    }

    /**
     * Get the what is included media.
     *
     * @return HasManyThrough
     */
    public function whatIsIncludedMedia(): HasManyThrough
    {
        return $this->uploads()->where('use_as', UploadUseAsEnum::WhatIsIncluded);
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     *
     * @return void
     */
    public static function bootHasManyUploads(): void
    {
        $model = new static;

        if (!in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) { // TODO: Replace with method_exists(static::class, 'bootSoftDeletes')
            static::deleted(function ($model) {
                $model->uploadables->each(function ($uploadable) {
                    $uploadable->delete();
                });
                // if ($model->uploads->count() > 0) {
                //     foreach ($model->uploads as $image) { // Delete the existing files on disk
                //         if ($image->url && Storage::disk(config('filesystems.default'))->exists($image->url)) Storage::disk(config('filesystems.default'))->delete($image->url);
                //     }

                //     $model->uploads->each(function ($upload) {
                //         $upload->delete();
                //     });
                // }
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                $model->uploadables->each(function ($uploadable) {
                    $uploadable->delete();
                });
            });
        }
    }
}
