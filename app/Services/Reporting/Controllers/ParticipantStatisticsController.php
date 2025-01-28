<?php

namespace App\Services\Reporting\Controllers;

use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\ParticipantDataService;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Reporting\ParticipantStatistics;

class ParticipantStatisticsController extends Controller
{
    use Response;

    public function __construct(protected ParticipantDataService $participantDataService)
    {
        parent::__construct();
    }

    /**
     * Participant Stats
     *
     * Get Participant Stats Summary.
     *
     * @group Participants
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam year string Specifying method of filtering query by year. Example: 2022
     * @queryParam status string Specifying method of filtering query by status.
     * @queryParam type string Specifying method of filtering query by type. Example: invoices
     * @queryParam period string Specifying method of filtering query by time period. Example: 24h
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        $validator = ParticipantStatistics::getParamsValidator(false);

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
            list($status, $year, $category, $period) = ParticipantStatistics::setParams($type, $status, $year, $category, $period);

            $stats = (new CacheDataManager(
                $this->participantDataService,
                'generateStatsSummary',
                [$type, $year, $status, $category, $period],
                false,
                false,
                null,
                null,
                true
            ))->getData();

            return $this->success('Participant Stats Summary', 200, array_merge($stats, ['query_params' => $parameters]));
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching stats.', 400, $exception->getMessage());
        }

    }

    /**
     * Participant Chart data
     *
     * @group Participants
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam type string required Specifying method of filtering query by type. Example: invoices
     * @queryParam year string Specifying method of filtering query by year. Example: 2022
     * @queryParam status string Specifying method of filtering query by status.
     * @queryParam period string Specifying method of filtering query by time period. Example: 24h
     *
     * @return JsonResponse
     */
    public function chart(): JsonResponse
    {
        $validator = ParticipantStatistics::getParamsValidator();

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
            list($status, $year, $category, $period) = ParticipantStatistics::setParams($type, $status, $year, $category, $period);

            $stats = (new CacheDataManager(
                $this->participantDataService,
                'generateYearGraphData',
                [$type, $year, $status, $category, $period],
                false,
                true
            ))->getData();

            return $this->success("Participant $type chart data.", 200, [
                'stats' => $stats,
                'query_params' => $parameters
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error("An error occurred while fetching $type chart data.", 400, $exception->getMessage());
        }
    }
}
