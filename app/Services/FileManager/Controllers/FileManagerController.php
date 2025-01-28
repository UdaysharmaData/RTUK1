<?php

namespace App\Services\FileManager\Controllers;

use App\Contracts\Uploadables\CanHaveManyUploadableResource;
use App\Contracts\Uploadables\CanHaveUploadableResource;
use App\Services\FileManager\FileManager;
use App\Traits\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class FileManagerController
{
    use Response;
    /**
     * @throws \Exception
     */
    public function upload(CanHaveUploadableResource|CanHaveManyUploadableResource $uploader, Request $request): \Illuminate\Http\JsonResponse
    {
        $uploader = (new FileManager($uploader));
        $this->getValidated($request, $uploader->rules);

        try {
            $response = $uploader->upload($request, 'file');

            return $this->success($response);
        } catch (\Exception $exception) {
            return $this->error($exception->getMessage(), 400);
        }
    }

    /**
     * @param Request $request
     * @return array
     */
    private function getValidated(Request $request, array $defaultRules): array
    {
        return $request->validate([
            ...$defaultRules,
            'file' => [
                'required',
                'file',
            ],
            'uploadable_type' => [
                "bail",
                "required",
                "string",
                function ($attribute, $value, $fail) {
                    if (!class_exists($value, true)) {
                        $fail("$value is not an existing class");
                    }

                    if (!in_array(Model::class, class_parents($value))) {
                        $fail("$value is not Illuminate\Database\Eloquent\Model");
                    }

                    if (
                        (!in_array(CanHaveUploadableResource::class, class_implements($value)))
                        || (!in_array(CanHaveManyUploadableResource::class, class_implements($value)))
                    ) {
                        $fail("$value does not support file Uploads");
                    }
                },
            ],

            // the id of the bookmarked object
            'uploadable_id' => [
                "required",
                function ($attribute, $value, $fail) {
                    $class = $this->input('uploadable_type');

                    if (!$class::where('id', $value)->exists()) {
                        $fail("$value does not exists in database");
                    }
                },
            ],
        ]);
    }
}
