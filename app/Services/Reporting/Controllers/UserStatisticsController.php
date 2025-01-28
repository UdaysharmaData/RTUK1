<?php

namespace App\Services\Reporting\Controllers;

use App\Facades\ClientOptions;
use App\Http\Controllers\Controller;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\UserDataService;
use App\Services\Reporting\UserStatistics;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UserStatisticsController extends Controller
{
    use Response;

    public function __construct(protected UserDataService $userDataService)
    {
        parent::__construct();
    }

    /**
     * Users Roles Stats
     *
     * Get User Role Stats.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam role string Specifying the user role to query by. Example: administrator
     * @queryParam year string Specifying method of filtering query by registration year. Example: 2022
     * @queryParam period string Specifying method of filtering query by time period. Example: 24h
     *
     * @return JsonResponse
     */
    public function summary(): \Illuminate\Http\JsonResponse
    {
        $parameters = array_filter(request()->query());

        try {
            $stats = (new CacheDataManager(
                $this->userDataService,
                'generateStatsSummary'
            ))->getData();

            return $this->success('User-Role Stats Summary', 200, [
                'stats' => $stats,
                'query_params' => $parameters,
                'options' => ClientOptions::only('users', ['reg_years', 'time_periods'])
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching stats.', 400);
        }
    }

    /**
     * Users Registration Stats
     *
     * Get User Registration Chart data.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam role string Specifying the user role to query by. Example: administrator
     * @queryParam year string Specifying method of filtering query by registration year. Example: 2022
     * @queryParam period string Specifying method of filtering query by time period. Example: 24h
     *
     * @return JsonResponse
     */
    public function chart(): \Illuminate\Http\JsonResponse
    {
        $parameters = array_filter(request()->query());

        try {
            $stats = (new CacheDataManager(
                $this->userDataService,
                'generateYearGraphData'
            ))->getData();

            return $this->success('User registration chart data.', 200, [
                'stats' => $stats,
                'query_params' => $parameters,
                'options' => ClientOptions::only('users', ['reg_years', 'time_periods'])
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching registration chart data.', 400);
        }
    }
}
