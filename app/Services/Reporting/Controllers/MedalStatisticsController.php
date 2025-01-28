<?php

namespace App\Services\Reporting\Controllers;

use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\MedalDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

use App\Traits\Response;
use App\Http\Controllers\Controller;
use App\Services\Reporting\MedalStatistics;

class MedalStatisticsController extends Controller
{
    use Response;

    public function __construct(protected MedalDataService $medalDataService)
    {
        parent::__construct();
    }

    /**
     * Medal Stats
     *
     * Get Medal Stats Summary.
     *
     * @group Medals
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam year string Specifying method of filtering query by year. Example: 2022
     * @queryParam period string Specifying method of filtering query by time period. Example: 24h
     * @queryParam _type string Specifying method of filtering query by medal type. Example: default
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        $validator = MedalStatistics::getParamsValidator(false);

        if ($validator->fails()) {
            return $this->error(
                'Invalid stats parameter(s) specified.',
                422,
                $validator->errors()->messages()
            );
        }

        $year = request('year');
        $period = request('period');
        $_type = request('_type');
        $parameters = array_filter(request()->query());

        try {
            list($year, $period, $_type) = MedalStatistics::setParams($year, $period, $_type);

            $stats = (new CacheDataManager(
                $this->medalDataService,
                'generateStatsSummary',
                [$year, $period, $_type],
                false,
                false,
                null,
                null,
                true
            ))->getData();

            return $this->success('Medal Stats Summary', 200, array_merge($stats, ['query_params' => $parameters]));
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching stats.', 400, $exception->getMessage());
        }

    }
}
