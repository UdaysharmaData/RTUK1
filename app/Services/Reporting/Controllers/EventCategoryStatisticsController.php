<?php

namespace App\Services\Reporting\Controllers;

use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EventCategoryDataService;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Reporting\EventCategoryStatistics;

class EventCategoryStatisticsController extends Controller
{
    use Response;

    public function __construct(protected EventCategoryDataService $eventCategoryDataService)
    {
        parent::__construct();
    }

    /**
     * Event Category Stats
     *
     * Get Event Category Stats Summary.
     *
     * @group Event Categories
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam year string Specifying method of filtering query by year. Example: 2022
     * @queryParam status string Specifying method of filtering query by status.
     * @queryParam period string Specifying method of filtering query by time period. Example: 24h
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        $validator = EventCategoryStatistics::getParamsValidator(false);

        if ($validator->fails()) {
            return $this->error(
                'Invalid stats parameter(s) specified.',
                422,
                $validator->errors()->messages()
            );
        }

        $year = request('year');
        $status = request('status');
        $period = request('period');
        $parameters = array_filter(request()->query());

        try {
            list($year, $status, $period) = EventCategoryStatistics::setParams($year, $status, $period);

            $stats = (new CacheDataManager(
                $this->eventCategoryDataService,
                'generateStatsSummary',
                [$year, $status, $period],
                false,
                false,
                null,
                null,
                true
            ))->getData();

            return $this->success('Event Category Stats Summary', 200, array_merge($stats, ['query_params' => $parameters]));
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching stats.', 400, $exception->getMessage());
        }
    }
}
