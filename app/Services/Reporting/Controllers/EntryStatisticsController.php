<?php

namespace App\Services\Reporting\Controllers;

use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EntryDataService;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Reporting\EntryStatistics;

class EntryStatisticsController extends Controller
{
    use Response;

    public function __construct(protected EntryDataService $entryDataService)
    {
        parent::__construct();
    }

    /**
     * Entries Stats
     *
     * Get Entries Stats Summary.
     *
     * @group Entries
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam year string Specifying method of filtering query by year. Example: 2022
     * @queryParam category string Specifying method of filtering query by category. Example: marathons
     * @queryParam type string Specifying method of filtering query by type. Example: entries
     * @queryParam period string Specifying method of filtering query by time period. Example: 24h
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        $validator = EntryStatistics::getParamsValidator(false);

        if ($validator->fails()) {
            return $this->error(
                'Invalid stats parameter(s) specified.',
                422,
                $validator->errors()->messages()
            );
        }

        $type = request('type');
        $year = request('year');
        $period = request('period');
        $category = request('category');
        $parameters = array_filter(request()->query());

        try {
            list($year, $category, $period) = EntryStatistics::setParams($type, $year, $category, $period);

            $stats = (new CacheDataManager(
                $this->entryDataService,
                'generateStatsSummary',
                [$type, $year, null, $category, $period],
                false,
                true,
                null,
                null,
                true
            ))->getData();

            return $this->success('Entries Stats Summary', 200, array_merge($stats, ['query_params' => $parameters]));
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching stats.', 400, $exception->getMessage());
        }
    }

    /**
     * Entries Chart data
     *
     * @group Entries
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam type string required Specifying method of filtering query by type. Example: entries
     * @queryParam year string Specifying method of filtering query by year. Example: 2022
     * @queryParam category string Specifying method of filtering query by category. Example: marathons
     * @queryParam period string Specifying method of filtering query by time period. Example: 24h
     *
     * @return JsonResponse
     */
    public function chart(): JsonResponse
    {
        $validator = EntryStatistics::getParamsValidator();

        if ($validator->fails()) {
            return $this->error(
                'Invalid chart parameter(s) specified.',
                422,
                $validator->errors()->messages()
            );
        }

        $type = request('type');
        $year = request('year');
        $period = request('period');
        $category = request('category');
        $parameters = array_filter(request()->query());

        try {
            list($year, $category, $period) = EntryStatistics::setParams($type, $year, $category, $period);

            $stats = (new CacheDataManager(
                $this->entryDataService,
                'generateYearGraphData',
                [$type, $year, null, $category, $period],
                false,
                true,
            ))->getData();

            return $this->success("Entries $type chart data.", 200, [
                'stats' => $stats,
                'query_params' => $parameters
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error("An error occurred while fetching $type chart data.", 400, $exception->getMessage());
        }
    }
}
