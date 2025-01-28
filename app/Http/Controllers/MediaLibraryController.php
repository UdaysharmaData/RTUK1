<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMediaLibraryRequest;
use App\Http\Requests\UpdateMediaLibraryRequest;
use App\Models\MediaLibrary;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MediaLibraryController extends Controller
{
    use Response;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        return $this->success('The list of media libraries', 200, [
            'libraries' => MediaLibrary::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreMediaLibraryRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreMediaLibraryRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $library = MediaLibrary::create($request->toArray());

            return $this->success('Successfully created the media library!', 201, [
                'library' => $library
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to create media library.', 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MediaLibrary  $mediaLibrary
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(MediaLibrary $mediaLibrary): \Illuminate\Http\JsonResponse
    {
        return $this->success('The media library details', 200, [
            'library' => $mediaLibrary
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MediaLibrary  $mediaLibrary
     * @return \Illuminate\Http\Response
     */
    public function edit(MediaLibrary $mediaLibrary)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMediaLibraryRequest  $request
     * @param  \App\Models\MediaLibrary  $mediaLibrary
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateMediaLibraryRequest $request, MediaLibrary $mediaLibrary): \Illuminate\Http\JsonResponse
    {
        try {
            $mediaLibrary->update($request->toArray());

            return $this->success('Successfully updated the media library!', 200, [
                'library' => $mediaLibrary
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to update media library', 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MediaLibrary  $mediaLibrary
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(MediaLibrary $mediaLibrary): \Illuminate\Http\JsonResponse
    {
        try {
            $mediaLibrary->delete();

            return $this->success('Successfully deleted the media library!', 200);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to delete media library.', 400);
        }
    }

    public function getRaceInfoWebsite(): JsonResponse
    {
        $currentDate = now();
        $race_info = DB::table('race_info')
            ->select(
                'race_info.name as title',
                'race_info.id as id',
                'race_info.expiration_date as expiration_date',
                'race_info.image_url as image_url',
                'uploads.url as url',
                'uploads.ref as ref'
            )
            ->join('uploads', 'uploads.id', '=', 'race_info.uploads_id')
            ->where('race_info.site_id', clientSiteId())
            ->where('race_info.expiration_date', '>', $currentDate)
            ->get();
        return $this->success('Successfully retrieved uploads pdf', 200, $race_info);
    }
}
