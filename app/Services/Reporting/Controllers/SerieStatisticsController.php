<?php

namespace App\Services\Reporting\Controllers;

use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\SerieDataService;
use App\Traits\Response;
use App\Services\Reporting\SerieStatistics;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class SerieStatisticsController extends Controller
{
    use Response;

    public function __construct(protected SerieDataService $serieDataService)
    {
        parent::__construct();
    }

    /**
     * Serie Stats
     *
     * Get Serie Stats Summary.
     *
     * @group Series
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
        $validator = SerieStatistics::getParamsValidator(false);

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
            list($year, $period) = SerieStatistics::setParams($year, $period);

            $stats = (new CacheDataManager(
                $this->serieDataService,
                'generateStatsSummary',
                [$year, $period],
                false,
                false,
                null,
                null,
                true
            ))->getData();

            return $this->success('Serie Stats Summary', 200,  array_merge($stats, ['query_params' => $parameters]));
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching stats.', 400, $exception->getMessage());
        }
    }
}
