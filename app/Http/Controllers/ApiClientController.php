<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApiClientRequest;
use App\Http\Requests\UpdateApiClientRequest;
use App\Models\ApiClient;
use App\Models\Article;
use App\Traits\Response;

class ApiClientController extends Controller
{
    use Response;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        $clients = ApiClient::query()
            ->latest()
            ->get();

        return $this->success('The list of clients', 200, [
            'clients' => $clients
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreApiClientRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreApiClientRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $client = ApiClient::create($request->validated());

            return $this->success('Successfully created the client!', 201, [
                'client' => $client
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to create client.', 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ApiClient  $apiClient
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ApiClient $apiClient): \Illuminate\Http\JsonResponse
    {
        return $this->success('The client details', 200, [
            'client' => $apiClient
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateApiClientRequest  $request
     * @param  \App\Models\ApiClient  $apiClient
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateApiClientRequest $request, ApiClient $apiClient): \Illuminate\Http\JsonResponse
    {
        try {
            $apiClient->update($request->validated());

            return $this->success('Successfully updated the client', 200, [
                'client' => $apiClient
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to update client.', 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ApiClient  $apiClient
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(ApiClient $apiClient): \Illuminate\Http\JsonResponse
    {
        try {
            $apiClient->delete();

            return $this->success('Successfully deleted the client!', 200);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to delete client.', 400);
        }
    }
}
