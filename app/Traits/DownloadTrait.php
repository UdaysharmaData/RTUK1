<?php

namespace App\Traits;

use Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait DownloadTrait
{
    /**
     * Download a resource (file/image)
     *
     * @param string $path
     * @param bool|null $deleteFileAfterSend
     * @param string|null $fileName
     * @param bool $sendAsAttachment
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    protected static function _download(string $path, ?bool $deleteFileAfterSend = false, ?string $fileName = null, bool $sendAsAttachment = false): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
       
        $fileName = $fileName ?? Str::substr($path, strrpos($path, '/') + 1); // Get the filename

        $disk = config('filesystems.default'); // Get the default disk

        if (! Storage::disk($disk)->exists($path)) { // Return this in case the file does not exists
            return response()->json([
                'status' => false,
                'message' => 'The resource was not found!'
            ], 404);
        }

        $headers = [
            'Content-Type' => Storage::disk($disk)->mimeType($path),
            'Access-Control-Expose-Headers' => ['Content-Disposition', 'File-Name'],
            'File-Name' => $fileName
        ];

        if ($sendAsAttachment) {
            return [
                'storage_path' => Storage::disk($disk)->path($path),
                'file_name' => $fileName,
                'headers' => $headers,
                'disk' => $disk,
                'path' => $path
            ];
        }

        if ($disk == 's3') {
            // $url = Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(10));
            // $url = Storage::disk($disk)->url($path);
            return Storage::disk($disk)->download($path, $fileName, $headers); 
           // TODO: @tsaffi - Ensure to delete this file after it gets downloaded (for file export)
            // return response()->download(Storage::disk($disk)->get($path), $fileName, $headers)->deleteFileAfterSend($deleteFileAfterSend);
        } else {
            return response()->download(Storage::disk($disk)->path($path), $fileName, $headers)->deleteFileAfterSend($deleteFileAfterSend);
        }

        // return Storage::disk($disk)->download($_path);
        // return Storage::disk($disk)->download($path);
        // return Storage::disk($disk)->response($path);
        // return response()->download(Storage::disk($disk)->get($path), $fileName, $headers)->deleteFileAfterSend($deleteFileAfterSend);
        // return response()->download(env('FILESYSTEM_DISK') == 's3' ? Storage::disk($disk)->url($path) : Storage::disk($disk)->path($path), $fileName, $headers)->deleteFileAfterSend($deleteFileAfterSend);

        // if ($deleteFileAfterSend) {
        //     Storage::disk($disk)->delete($path);
        // }
    }

    
    /**
     * Generate the minimum required data for a file download.
     *
     * @param string $path The path to the file.
     *
     * @return array|JsonResponse The response to be returned.
     */
    protected static function generateMinTypeForFile(string $path): JsonResponse|array
    {
       
      
      
        $fileName = $fileName ?? Str::substr($path, strrpos($path, '/') + 1); // Get the filename

        $disk = config('filesystems.default'); // Get the default disk

        if (! Storage::disk($disk)->exists($path)) { // Return this in case the file does not exists
            return response()->json([
                'status' => false,
                'message' => 'The resource was not found!'
            ], 404);
        }

        $headers = [
            'Content-Type' => Storage::disk($disk)->mimeType($path),
            'Access-Control-Expose-Headers' => ['Content-Disposition', 'File-Name'],
            'File-Name' => $fileName
        ];

        return [
            'storage_path' => Storage::disk($disk)->path($path),
            'file_name' => $fileName,
            'headers' => $headers,
            'disk' => $disk,
            'path' => $path,
            's3PathLink'=> ($disk == 's3') ? Storage::disk('s3')->url($path) : ''
        ];


    }
}
