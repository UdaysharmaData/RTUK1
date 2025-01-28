<?php

namespace App\Services\Reporting\Controllers;

use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\CityDataService;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Reporting\CityStatistics;

class CityStatisticsController extends Controller
{
    use Response;

    public function __construct(protected CityDataService $cityDataService)
    {
        parent::__construct();
    }

    /**
     * City Stats
     *
     * Get City Stats Summary.
     *
     * @group Cities
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam year string Specifying method of filtering query by year. Example: 2022
     * @queryParam period string Specifying method of filtering query by time period. Example: 24h
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        $validator = CityStatistics::getParamsValidator(false);

        if ($validator->fails()) {
            return $this->error(
                'Invalid stats parameter(s) specified.',
                422,
                $validator->errors()->messages()
            );
        }

        $year = request('year');
        $period = request('period');
        $parameters = array_filter(request()->query());

        try {
            list($year, $period) = CityStatistics::setParams($year, $period);

            $stats = (new CacheDataManager(
                $this->cityDataService,
                'generateStatsSummary',
                [$year, $period],
                false,
                false,
                null,
                null,
                true
            ))->getData();

            return $this->success('City Stats Summary', 200, array_merge($stats, ['query_params' => $parameters]));
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching stats.', 400, $exception->getMessage());
        }
    }
}
