<?php

namespace App\Services\Reporting\Controllers;

use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\CombinationDataService;
use App\Services\Reporting\CombinationStatistics;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class CombinationStatisticsController extends Controller
{
    use Response;

    public function __construct(protected CombinationDataService $combinationDataService)
    {
        parent::__construct();
    }

    /**
     * Combination Stats
     *
     * Get Combination Stats Summary.
     *
     * @group Combination
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam year string Specifying method of filtering query by year. Example: 2022
     * @queryParam period string Specifying method of filtering query by time period. Example: 24h
     * @queryParam type string Specifying method of filtering query by related entity types. Example: cities
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        $validator = CombinationStatistics::getParamsValidator(false);

        if ($validator->fails()) {
            return $this->error(
                'Invalid stats parameter(s) specified.',
                422,
                $validator->errors()->messages()
            );
        }

        $year = request('year');
        $period = request('period');
        $type = request('type');
        $parameters = array_filter(request()->query());

        try {
            list($year, $period, $type) = CombinationStatistics::setParams($year, $period, $type);

            $stats = (new CacheDataManager(
                $this->combinationDataService,
                'generateStatsSummary',
                [$year, $period, $type]
            ))->getData();

            return $this->success('Combination Stats Summary', 200, array_merge($stats, ['query_params' => $parameters]));
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching stats.', 400, $exception->getMessage());
        }
    }
}
