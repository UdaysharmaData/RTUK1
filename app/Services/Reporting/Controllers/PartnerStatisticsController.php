<?php

namespace App\Services\Reporting\Controllers;

use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\PartnerDataService;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Reporting\PartnerStatistics;

class PartnerStatisticsController extends Controller
{
    use Response;

    public function __construct(protected PartnerDataService $partnerDataService)
    {
        parent::__construct();
    }

    /**
     * Partner Stats
     *
     * Get Partner Stats Summary.
     *
     * @group Partners
     * @authenticated
     * @header Content-Type application/json
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        try {
            $stats = (new CacheDataManager(
                $this->partnerDataService,
                'generateStatsSummary',
                [],
                false,
                false,
                null,
                null,
                true
            ))->getData();
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching stats.', 400, $exception->getMessage());
        }

        return $this->success('Partner Stats Summary', 200, $stats);
    }
}
