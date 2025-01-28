<?php

namespace App\Services\Reporting\Controllers;

use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EnquiryDataService;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Reporting\EnquiryStatistics;

class EnquiryStatisticsController extends Controller
{
    use Response;

    public function __construct(protected EnquiryDataService $enquiryDataService)
    {
        parent::__construct();
    }

    /**
     * Enquiries Stats
     *
     * Get Enquiries Stats Summary.
     *
     * @group Enquiries
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam year string Specifying method of filtering query by year. Example: 2022
     * @queryParam status string Specifying method of filtering query by status.
     * @queryParam category string Specifying method of filtering query by category. Example: marathons
     * @queryParam type string Specifying method of filtering query by type. Example: enquiries
     * @queryParam period string Specifying method of filtering query by time period. Example: 24h
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        $validator = EnquiryStatistics::getParamsValidator(false);

        if ($validator->fails()) {
            return $this->error(
                'Invalid stats parameter(s) specified.',
                422,
                $validator->errors()->messages()
            );
        }

        $type = request('type');
        $year = request('year');
        $status = request('status');
        $period = request('period');
        $category = request('category');
        $parameters = array_filter(request()->query());

        try {
            list($status, $year, $category, $period) = EnquiryStatistics::setParams($type, $status, $category, $year, $period);

            $stats = (new CacheDataManager(
                $this->enquiryDataService,
                'generateStatsSummary',
                [$type, $year, $status, $category, $period]
            ))->getData();

            return $this->success('Enquiries Stats Summary', 200, array_merge($stats, ['query_params' => $parameters]));
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching stats.', 400, $exception->getMessage());
        }
    }

    /**
     * Enquiries Chart data
     *
     * @group Enquiries
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam type string required Specifying method of filtering query by type. Example: enquiries
     * @queryParam year string Specifying method of filtering query by year. Example: 2022
     * @queryParam status string Specifying method of filtering query by status.
     * @queryParam category string Specifying method of filtering query by category. Example: marathons
     * @queryParam period string Specifying method of filtering query by time period. Example: 24h
     *
     * @return JsonResponse
     */
    public function chart(): JsonResponse
    {
        $validator = EnquiryStatistics::getParamsValidator();

        if ($validator->fails()) {
            return $this->error(
                'Invalid chart parameter(s) specified.',
                422,
                $validator->errors()->messages()
            );
        }

        $type = request('type');
        $year = request('year');
        $status = request('status');
        $period = request('period');
        $category = request('category');
        $parameters = array_filter(request()->query());

        try {
            list($status, $year, $category, $period) = EnquiryStatistics::setParams($type, $status, $category, $year, $period);

            $stats = (new CacheDataManager(
                $this->enquiryDataService,
                'generateYearGraphData',
                [$type, $year, $status, $category, $period]
            ))->getData();

            return $this->success("Enquiries $type chart data.", 200, [
                'stats' => $stats,
                'query_params' => $parameters
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error("An error occurred while fetching $type chart data.", 400, $exception->getMessage());
        }
    }
}
