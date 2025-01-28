<?php

namespace App\Services\Reporting\Controllers;

use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\ExternalEnquiryDataService;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Reporting\ExternalEnquiryStatistics;

class ExternalEnquiryStatisticsController extends Controller
{
    use Response;

    public function __construct(protected ExternalEnquiryDataService $externalEnquiryDataService)
    {
        parent::__construct();
    }

    /**
     * External Enquiries Stats
     *
     * Get External Enquiries Stats Summary.
     *
     * @group External Enquries
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
        $validator = ExternalEnquiryStatistics::getParamsValidator(false);

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
            list($status, $year, $category, $period) = ExternalEnquiryStatistics::setParams($type, $status, $category, $year, $period);

            $stats = (new CacheDataManager(
                $this->externalEnquiryDataService,
                'generateStatsSummary',
                [$type, $year, $status, $category, $period],
                false,
                false,
                null,
                null,
                true
            ))->getData();

            return $this->success('External enquries Stats Summary', 200, array_merge($stats, ['query_params' => $parameters]));
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching stats.', 400, $exception->getMessage());
        }
    }

    /**
     * External Enquries Chart data
     *
     * @group External Enquries
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
        $validator = ExternalEnquiryStatistics::getParamsValidator();

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
            list($status, $year, $category, $period) = ExternalEnquiryStatistics::setParams($type, $status, $category, $year, $period);

            $stats = (new CacheDataManager(
                $this->externalEnquiryDataService,
                'generateYearGraphData',
                [$type, $year, $status, $category, $period]
            ))->getData();

            return $this->success("External enquiries $type chart data.", 200, [
                'stats' => $stats,
                'query_params' => $parameters
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error("An error occurred while fetching $type chart data.", 400, $exception->getMessage());
        }
    }
}
