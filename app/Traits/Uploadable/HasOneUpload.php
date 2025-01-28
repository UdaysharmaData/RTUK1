<?php

namespace App\Traits\Uploadable;

use App\Models\Upload;
use App\Models\Uploadable;

use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasOneUpload
{
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
     * upload
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

    public function uploadUrl(): ?string
    {
        return $this->upload()->exists() && isset($this->upload->url) ? $this->upload->url : null;
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     *
     * @return void
     */
    public static function bootHasOneUpload(): void
    {
        $model = new static;

        if (!in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) { // TODO: Replace with method_exists(static::class, 'bootSoftDeletes') // TODO: Replace with method_exists(static::class, 'bootSoftDeletes')
            static::deleted(function ($model) {
                if ($uploadable = $model->uploadable) { // Delete the record
                    $uploadable->delete();
                }
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                if ($uploadable = $model->uploadable) { // Delete the record
                    $uploadable->delete();
                }
            });
        }
    }
}
