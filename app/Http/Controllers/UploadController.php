<?php

namespace App\Http\Controllers;


use App\Enums\UploadImageSizeVariantEnum;
use App\Enums\UploadTypeEnum;
use App\Facades\ClientOptions;
use App\Filters\UploadsOrderByFilter;
use App\Filters\YearFilter;
use App\Http\Requests\UploadCreateRequest;
use App\Http\Requests\UploadListingQueryParamsRequest;
use App\Http\Requests\UploadUpdateRequest;
use App\Models\Upload;
use App\Services\FileManager\Exceptions\UnableToOpenFileFromUrlException;
use App\Services\FileManager\UploadFileManager;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @group Upload
 * 
 * APIs for managing uploads
 * @authenticated
 * @header Content-Type application/json
 */
class UploadController
{
    use Response;

    /**
     * The list of uploads
     * 
     * @queryParam per_page int The number of uploads to return. Example: 10
     * @queryParam type string The type of upload. Example: image
     * @queryParam term string The search term. Example: image
     * @queryParam year int The year of the upload. Example: 2021
     * @queryParam page int The page number. Example: 1
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example:created_at:desc
     *
     * @param UploadListingQueryParamsRequest $request
     * @return JsonResponse
     */
    public function index(UploadListingQueryParamsRequest $request): JsonResponse
    {
        $uploads = Upload::withCount('uploadables')
            ->whereNotIn('type', [UploadTypeEnum::PDF, UploadTypeEnum::CSV])
            ->when($request->filled('type'), function ($query) use ($request) {
                $query->where('type', $request->type);
            })->when($request->filled('term'), function ($query) use ($request) {
                $query->where('title', 'like', "%{$request->term}%")
                    ->orWhere('caption', 'like', "%{$request->term}%")
                    ->orWhere('alt', 'like', "%{$request->term}%");
            })
            ->where('private', false)
            ->when(!$request->filled('order_by'), function ($query) {
                $query->orderBy('created_at', 'desc');
            })->filterListBy(new YearFilter)
            ->filterListBy(new UploadsOrderByFilter)
            ->paginate($request->per_page ?? 10);

        return $this->success('Successfully retrieved uploads', 200, [
            'uploads' => $uploads,
            'options' => ClientOptions::all('uploads')
        ]);
    }

    public function getUploadPdf(UploadListingQueryParamsRequest $request): JsonResponse
    {
        $uploads = Upload::withCount('uploadables')
            ->whereNotIn('type', [UploadTypeEnum::Image, UploadTypeEnum::CSV, UploadTypeEnum::Video, UploadTypeEnum::Audio])
            ->when($request->filled('type'), function ($query) use ($request) {
                $query->where('type', $request->type);
            })->when($request->filled('term'), function ($query) use ($request) {
                $query->where('title', 'like', "%{$request->term}%")
                    ->orWhere('caption', 'like', "%{$request->term}%")
                    ->orWhere('alt', 'like', "%{$request->term}%");
            })
            ->where('private', false)
            ->when(!$request->filled('order_by'), function ($query) {
                $query->orderBy('created_at', 'desc');
            })->filterListBy(new YearFilter)
            ->filterListBy(new UploadsOrderByFilter)
            ->paginate($request->per_page ?? 10);

        return $this->success('Successfully retrieved uploads pdf', 200, [
            'uploads' => $uploads,
            'options' => ClientOptions::all('uploads')
        ]);
    }

    public function raceInfoAdd(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'expiration_date' => 'required|date',
            'uploads_id' => 'required|integer',
            'image_url' => 'url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422); // Unprocessable Entity
        }

        try {
            DB::table('race_info')->insert([
                'site_id' => clientSiteId(),
                'ref' => Str::orderedUuid()->toString(),
                'name' => $request->name,
                'expiration_date' => $request->expiration_date,
                'uploads_id' => $request->uploads_id,
                'image_url' => $request->image_url,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Race info added successfully.'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add race info. ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRaceInfo(): JsonResponse
    {
        $currentDate = now();
        $race_info = DB::table('race_info')
            ->select(
                'race_info.name as title',
                'race_info.id as id',
                'race_info.ref as ref',
                'race_info.expiration_date as expiration_date',
                'race_info.image_url as image_url',
                'uploads.url as url'
            )
            ->join('uploads', 'uploads.id', '=', 'race_info.uploads_id')
            ->where('race_info.site_id', clientSiteId())
            ->where('race_info.expiration_date', '>', $currentDate)
            ->get();
        return $this->success('Successfully retrieved uploads pdf', 200, $race_info);
    }

    public function deleteRaceInfo(Request $request): JsonResponse
    {
        $ref = $request->input('ref');
        $deleted = DB::table('race_info')->where('ref', $ref)->delete();
        if ($deleted) {
            return response()->json([
                'message' => 'Race info deleted successfully.',
            ], 200);
        } else {
            return response()->json([
                'message' => 'Failed to delete race info. It may not exist.',
            ], 400);
        }
    }


    /**
     * Show the form for creating a new resource.
     *
     * @urlParam upload string required The ref attribute of the upload. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     * 
     * @return JsonResponse
     */
    public function show(string $uploadRef): JsonResponse
    {
        $upload = Upload::withCount('uploadables')->where('ref', $uploadRef)->firstOrFail();

        return $this->success('Successfully retrieved upload', 200, [
            'upload' => $upload
        ]);
    }

    /**
     * upload files
     *
     * @param  mixed $request
     * @return JsonResponse
     */
    public function store(UploadCreateRequest $request): JsonResponse
    {
        try {
            $uploads = (new UploadFileManager())->storeFiles($request->get('media'));
        } catch (UnableToOpenFileFromUrlException $exception) {
            return $this->error($exception->getMessage(), 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to upload file.', 400);
        }

        return $this->success("files uploaded successfully", 201, [
            'uploads' => $uploads
        ]);
    }

    /**
     * Update file
     * 
     * @urlParam upload string required The ref attribute of the upload. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param  UploadUpdateRequest $request
     * @param  Upload $upload
     * @return JsonResponse
     */
    public function update(UploadUpdateRequest $request, Upload $upload): JsonResponse
    {
        try {
            $upload = (new UploadFileManager())->updateFile($upload, $request->get('media'));
        } catch (UnableToOpenFileFromUrlException $exception) {
            return $this->error($exception->getMessage(), 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to update upload resource.', 400);
        }

        return $this->success('Successfully updated the upload', 200, [
            'upload' => $upload
        ]);
    }


    /**
     * Delete uploaded resource
     * 
     * Remove the specified resource from storage.
     *
     * @urlParam upload string required The ref attribute of the upload. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param Upload $upload
     * @return JsonResponse
     */
    public function destroy(Upload $upload): JsonResponse
    {
        try {
            $upload->delete();

            return $this->success('Successfully deleted the upload');
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to delete upload resource.', 400);
        }
    }

    /**
     * Delete multiple uploaded resources
     * 
     * Remove multiple resources from storage.
     * 
     * @bodyParam uploads array required The list of upload ref attributes. Example: ["9762db71-f5a6-41c4-913e-90b8aebad733", "9762db71-f5a6-41c4-913e-90b8aebad733"]    
     *
     * @param  mixed $request
     * @return JsonResponse
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $uploads = $request->get('uploads');

        Upload::whereIn('ref', $uploads)->each(function ($upload) {
            $upload->delete();
        });

        return $this->success('Successfully deleted the uploads');
    }

    /**
     * Get image version storage link
     * 
     * Get the storage link for the specified image version.
     * 
     * @queryParam image_path string required The path to the image. Example: uploads/images/2021/09/9762db71-f5a6-41c4-913e-90b8aebad733.jpg
     * @queryParam device_version string required The device version. Example: card
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function getImageVersionStorageLink(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image_path' => 'required|string',
            'image_version' => ['required', new Enum(UploadImageSizeVariantEnum::class)],
        ]);

        if ($validator->failed()) {
            return $this->error('Invalid request', 400, $validator->errors());
        }

        $imagePath = $request->get('image_path');
        $imageVersion = $request->get('image_version');

        if (Storage::disk(config('filesystems.default'))->exists($imagePath)) {
            $storageLink = Upload::resolveResourceUrl($imagePath);
        } else {
            $originalImagePath = str_replace("_$imageVersion", "", $imagePath);

            if (Storage::disk(config('filesystems.default'))->exists($originalImagePath)) {
                $storageLink = Upload::resolveResourceUrl($originalImagePath);
            } else {
                return $this->error('Image not found', 404);
            }
        }

        return $this->success(
            "Image found",
            200,
            $storageLink
        );
    }
}
