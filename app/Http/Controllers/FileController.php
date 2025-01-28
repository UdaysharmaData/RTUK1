<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\Upload;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use App\Traits\Response;
use App\Traits\DownloadTrait;

/**
 * @group File
 * Manages files download on the application
 */
class FileController extends Controller
{
    use Response;

    /*
    |--------------------------------------------------------------------------
    | File Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with files.
    |
    */

    use DownloadTrait;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Download file from public/private directory
     * 
     * @urlParam path string required The path to the resource. Example: https://test.sportforcharity.com/storage/uploads/media/images/kvWpjp9km1JECSAD.png
     * 
     * @param  string                           $path
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function download(string $path): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        if (! Str::contains($path, 'uploads/public') && !Str::contains($path, 'uploads/private')) {
            $path = Str::replace('storage', 'uploads/public',  $path);
        }

        $_path = Str::substr($path, strpos($path, 'uploads')); // Get the storage path

        return static::_download($_path);
    }

    /**
     * Update a file's details
     *
     * @urlParam upload string required The ref of the upload. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  Request       $request
     * @param  Upload        $upload
     * @return JsonResponse
     */
    public function updateInfo(Request $request, Upload $upload): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'nullable', 'string'],
            'alt' => ['sometimes', 'nullable', 'string'],
            'caption' => ['sometimes', 'nullable', 'string']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $upload = Upload::where('ref', $upload->ref)->firstOrFail();

            try {
                $upload->update($request->all());

                $upload->uploadable->touch();
                
            } catch (QueryException $e) {
                return $this->error('Unable to update the file\'s details!', 406, $e->getMessage());   
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The file was not found!', 404);
        }

        return $this->success('The file\'s details have been successfully updated!', 200, $upload->fresh());
    }
}