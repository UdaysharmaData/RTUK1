<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApiClientCareerRequest;
use App\Http\Requests\UpdateApiClientCareerRequest;
use App\Models\ApiClientCareer;
use App\Traits\Response;

class ApiClientCareerController extends Controller
{
    use Response;
    /**
     * Careers
     *
     * Get Careers Listing.
     *
     * @group Careers
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        $careers = ApiClientCareer::getCachedCareersCollection();

        return $this->success('Careers List', 200, [
            'careers' => $careers
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreApiClientCareerRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreApiClientCareerRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $career = ApiClientCareer::create($request->validated());

            return $this->success('Successfully created career listing!', 201, [
                'career' => $career,
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to create career listing.', 400);
        }
    }

    /**
     * Specific Career
     *
     * Get Specific Career.
     *
     * @group Careers
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @urlParam career string required The ref attribute of specific career. Example: 975dcafb-78da-4e28-99f4-c69f636494be
     *
     * @param  \App\Models\ApiClientCareer  $career
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ApiClientCareer $career): \Illuminate\Http\JsonResponse
    {
        return $this->success('Get career by ref', 200, [
            'career' => $career
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ApiClientCareer  $apiClientCareer
     * @return \Illuminate\Http\Response
     */
    public function edit(ApiClientCareer $apiClientCareer)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateApiClientCareerRequest  $request
     * @param  \App\Models\ApiClientCareer  $apiClientCareer
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateApiClientCareerRequest $request, ApiClientCareer $apiClientCareer): \Illuminate\Http\JsonResponse
    {
        try {
            $apiClientCareer->update($request->validated());

            return $this->success('Successfully updated career listing!', 201, [
                'career' => $apiClientCareer
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to update career listing.', 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ApiClientCareer  $apiClientCareer
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(ApiClientCareer $apiClientCareer): \Illuminate\Http\JsonResponse
    {
        try {
            $apiClientCareer->delete();

            return $this->success('Successfully deleted the career listing');
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to delete career listing.', 400);
        }
    }
}
