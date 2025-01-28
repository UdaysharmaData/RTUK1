<?php

namespace App\Services\Reporting\Controllers;

use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\InvoiceDataService;
use App\Traits\Response;
use App\Services\Reporting\InvoiceStatistics;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class InvoiceStatisticsController extends Controller
{
    use Response;

    public function __construct(protected InvoiceDataService $invoiceDataService)
    {
        parent::__construct();
    }

    /**
     * Invoice Stats
     *
     * Get Invoice Stats Summary.
     *
     * @group Invoices
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam year string Specifying method of filtering query by year. Example: 2022
     * @queryParam status string Specifying method of filtering query by status.
     * @queryParam category string Specifying method of filtering query by category (ref for event categories). Example: 98677146-d86a-4b10-a694-d79eb66e8220
     * @queryParam type string Specifying method of filtering query by type. Example: invoices
     * @queryParam period string Specifying method of filtering query by time period. Example: 24h
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        $validator = InvoiceStatistics::getParamsValidator(false);

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
        $parameters = array_filter(request()->query());

        try {
            list($status, $year, $period) = InvoiceStatistics::setParams($type, $status, $year, $period);

            $stats = (new CacheDataManager(
                $this->invoiceDataService,
                'generateStatsSummary',
                [$type, $year, $status, $period],
                false,
                false,
                null,
                null,
                true
            ))->getData();

            return $this->success('Invoices Stats Summary', 200, array_merge($stats, ['query_params' => $parameters]));
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching stats.', 400, $exception->getMessage());
        }

    }

    /**
     * Invoice Chart data
     *
     * @group Invoices
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam type string required Specifying method of filtering query by type. Example: invoices
     * @queryParam year string Specifying method of filtering query by year. Example: 2022
     * @queryParam status string Specifying method of filtering query by status.
     * @queryParam category string Specifying method of filtering query by category (ref for event categories). Example: 98677146-d86a-4b10-a694-d79eb66e8220
     * @queryParam period string Specifying method of filtering query by time period. Example: 24h
     *
     * @return JsonResponse
     */
    public function chart(): JsonResponse
    {
        $validator = InvoiceStatistics::getParamsValidator();

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
        $parameters = array_filter(request()->query());

        try {
            list($status, $year, $period) = InvoiceStatistics::setParams($type, $status, $year, $period);

            $stats = (new CacheDataManager(
                $this->invoiceDataService,
                'generateYearGraphData',
                [$type, $year, $status, $period]
            ))->getData();

            return $this->success("Invoice $type chart data.", 200, [
                'stats' => $stats,
                'query_params' => $parameters
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error("An error occurred while fetching $type chart data.", 400, $exception->getMessage());
        }
    }
}
