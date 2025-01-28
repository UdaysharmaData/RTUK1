<?php

namespace App\Http\Controllers;

use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use App\Models\MediaLibraryCollection;
use App\Http\Requests\StoreMediaLibraryCollectionRequest;
use App\Http\Requests\UpdateMediaLibraryCollectionRequest;

class MediaLibraryCollectionController extends Controller
{
    use Response;

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return $this->success('The list of media library collections', 200, [
            'collections' => MediaLibraryCollection::all()
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
     * @param  \App\Http\Requests\StoreMediaLibraryCollectionRequest  $request
     * @return JsonResponse
     */
    public function store(StoreMediaLibraryCollectionRequest $request): JsonResponse
    {
        try {
            $collection = MediaLibraryCollection::create($request->toArray());

            return $this->success('Successfully created the media library collection!', 201, [
                'collection' => $collection
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to create media library collection.', 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MediaLibraryCollection  $mediaCollection
     * @return JsonResponse
     */
    public function show(MediaLibraryCollection $mediaCollection): JsonResponse
    {
        return $this->success('The media library collection details', 200, [
            'collection' => $mediaCollection
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MediaLibraryCollection  $mediaCollection
     * @return \Illuminate\Http\Response
     */
    public function edit(MediaLibraryCollection $mediaCollection)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMediaLibraryCollectionRequest  $request
     * @param  \App\Models\MediaLibraryCollection  $mediaCollection
     * @return JsonResponse
     */
    public function update(UpdateMediaLibraryCollectionRequest $request, MediaLibraryCollection $mediaCollection): JsonResponse
    {
        try {
            $mediaCollection->update($request->toArray());

            return $this->success('Successfully updated the media library collection!', 200, [
                'collection' => $mediaCollection
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to update media library collection', 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MediaLibraryCollection  $mediaCollection
     * @return JsonResponse
     */
    public function destroy(MediaLibraryCollection $mediaCollection): JsonResponse
    {
        try {
            $mediaCollection->delete();

            return $this->success('Successfully deleted the media library collection!', 200);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to delete media library collection.', 400);
        }
    }
}
