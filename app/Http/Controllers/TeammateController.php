<?php

namespace App\Http\Controllers;

use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use App\Http\Requests\StoreTeammateRequest;
use App\Http\Requests\UpdateTeammateRequest;
use App\Models\Teammate;
use App\Services\FileManager\Traits\SingleUploadModel;
use App\Services\FileManager\Traits\UploadModelTrait;
use App\Traits\Response;
use Illuminate\Support\Facades\Storage;

class TeammateController extends Controller
{
    use Response, SingleUploadModel, UploadModelTrait;

    /**
     * Team
     *
     * API client's team listing.
     *
     * @group Team
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        return $this->success('The list of teammates', 200, [
            'teammates' => Teammate::all()
        ]);
    }

    /**
     * Add Teammate
     *
     * Add a new API client teammate.
     *
     * @group Team
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam name string required The full name of the team member. Example: John Snow
     * @bodyParam title string required The role/position occupied by this teammate. Example: Senior Accountant
     * @bodyParam image file required The image
     *
     * @param  \App\Http\Requests\StoreTeammateRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTeammateRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $teammate = Teammate::create($request->validated());

            $this->attachSingleUploadToModel($teammate, $request->image, UploadUseAsEnum::Image, true);

            return $this->success('Successfully added the teammate!', 201, [
                'teammate' => $teammate->refresh()
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to add teammate.', 400);
        }
    }

    /**
     * Get Teammate
     *
     * Retrieve specific teammate.
     *
     * @group Team
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @urlParam teammate string required The ref attribute of the model being updated. Example: 97625fec-309e-44c2-a580-94c7a25d1951
     *
     * @param  \App\Models\Teammate  $teammate
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Teammate $teammate): \Illuminate\Http\JsonResponse
    {
        return $this->success('The teammate details', 200, [
            'teammate' => $teammate
        ]);
    }

    /**
     * Update Teammate
     *
     * Update API client teammate.
     *
     * @group Team
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam teammate string required The ref attribute of the model being updated. Example: 97625fec-309e-44c2-a580-94c7a25d1951
     * @bodyParam name string The full name of the team member. Example: John Snow
     * @bodyParam title string The role/position occupied by this teammate. Example: Senior Accountant
     * @bodyParam image file The image
     *
     * @param  \App\Http\Requests\UpdateTeammateRequest  $request
     * @param  \App\Models\Teammate  $teammate
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateTeammateRequest $request, Teammate $teammate): \Illuminate\Http\JsonResponse
    {
        try {
            $teammate = tap($teammate)->update($request->validated());

            $this->attachSingleUploadToModel($teammate, $request->image, UploadUseAsEnum::Image, true);

            return $this->success('Successfully updated the teammate!', 200, [
                'teammate' => $teammate
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to update teammate', 400);
        }
    }

    /**
     * Delete Teammate
     *
     * Delete API client teammate.
     *
     * @group Team
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam teammate string required The ref attribute of the model being updated. Example: 97625fec-309e-44c2-a580-94c7a25d1951
     *
     * @param  \App\Models\Teammate  $teammate
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Teammate $teammate): \Illuminate\Http\JsonResponse
    {
        try {
            if ($upload = $teammate->upload) {
                $this->detachUpload($teammate, $upload->ref);
            };
            $teammate->delete();

            return $this->success('Successfully deleted the teammate!', 200);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to delete teammate.', 400);
        }
    }
}
