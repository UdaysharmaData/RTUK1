<?php

namespace App\Traits\Uploadable;

use App\Models\Upload;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Storage;

trait HasBackgroundImageUpload
{
    public function background() :MorphOne
    {
        return $this->morphOne(Upload::class, 'uploadable');
    }

    public function backgroundUrl(): ?string
    {
        return $this->background()->exists()
        && isset($this->background->url)
            ? $this->background->url
            : null;
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     *
     * @return void
     */
    public static function bootHasBackgroundImageUpload(): void
    {
        $model = new static;

        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) { // TODO: Replace with method_exists(static::class, 'bootSoftDeletes')
            static::deleted(function ($model) {
                if ($model->background) { // Delete the existing files on disk, then the record
                    if ($model->background->url && Storage::disk(config('filesystems.default'))->exists($model->background->url)) Storage::disk(config('filesystems.default'))->delete($model->background->url);

                    $model->background?->delete();
                }
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                if ($model->background) { // Delete the existing files on disk, then the record
                    if ($model->background->url && Storage::disk(config('filesystems.default'))->exists($model->background->url)) Storage::disk(config('filesystems.default'))->delete($model->background->url);

                    $model->background?->delete();
                }
            });
        }
    }
}
