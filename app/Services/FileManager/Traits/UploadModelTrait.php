<?php

namespace App\Services\FileManager\Traits;

use App\Contracts\Uploadables\CanHaveManyUploadableResource;
use App\Contracts\Uploadables\CanHaveUploadableResource;
use App\Enums\UploadUseAsEnum;
use App\Models\Upload;

trait UploadModelTrait
{
    /**
     * Attach multiple uploads to a model
     *
     * @param  CanHaveManyUploadableResource $model
     * @param  array $uploadRefs
     * @param  UploadUseAsEnum $useAs
     * @return CanHaveManyUploadableResource
     */
    public function attachMultipleUploadsToModel(CanHaveManyUploadableResource $model, array $uploadRefs, UploadUseAsEnum $useAs = UploadUseAsEnum::Gallery)
    {
        $uploads = Upload::whereIn('ref', $uploadRefs)->get();

        foreach ($uploads as $upload) {
            $model->uploadables()->updateOrCreate([
                'upload_id' => $upload->id,
                'use_as' => $useAs
            ]);
        }
    }

    /**
     * Attach a single upload to a model
     *
     * @param  CanHaveUploadableResource|CanHaveManyUploadableResource $model
     * @param  string $uploadRef
     * @param  UploadUseAsEnum $useAs
     */
    public function attachSingleUploadToModel(CanHaveUploadableResource|CanHaveManyUploadableResource $model, string $uploadRef, UploadUseAsEnum $useAs = UploadUseAsEnum::Image, $private = false)
    {
        $upload = Upload::where('ref', $uploadRef)->first();

        if ($upload) {
            $uploadable = $model->uploadable()->where('use_as', $useAs)->where('upload_id', '!=', $upload->id)->first();

            if ($uploadable) {
                $uploadable->delete();
            }

            $uploadable = $model->uploadable()->updateOrCreate([
                'upload_id' => $upload->id,
                'use_as' => $useAs
            ]);

            if ($private) {
                $upload->update(['private' => true]);
            }

        } 
    }


    /**
     * Detach an upload from a model
     *
     * @param  CanHaveUploadableResource|CanHaveManyUploadableResourcee $model
     * @param  string $uploadRef
     * @return CanHaveUploadableResource|CanHaveManyUploadableResource
     */
    public function detachUpload(CanHaveUploadableResource|CanHaveManyUploadableResource $model, string $uploadRef): CanHaveUploadableResource|CanHaveManyUploadableResource
    {
        $uploadable = $model->uploadable()->whereHas('upload', function ($query) use ($uploadRef) {
            $query->where('ref', $uploadRef);
        })->first();

        if ($uploadable) {
            $uploadable->delete();
        } 
        
        return $model;
    }
}
