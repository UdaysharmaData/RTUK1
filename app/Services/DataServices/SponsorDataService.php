<?php

namespace App\Services\DataServices;

use App\Modules\Event\Models\Sponsor;
use App\Services\ConfigurableEventPropertyService;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\DataServices\Traits\ConfigurableEventPropertyServiceTrait;
use App\Services\Reporting\SponsorStatistics;

class SponsorDataService implements DataServiceInterface
{
    use ConfigurableEventPropertyServiceTrait;

    /**
     * @var ConfigurableEventPropertyService
     */
    private ConfigurableEventPropertyService $eventPropertyService;

    public function __construct()
    {
        $this->eventPropertyService = new ConfigurableEventPropertyService(Sponsor::query(), ['site']);
    }

    /**
     * @param $year
     * @param $period
     * @return array
     */
    public function generateStatsSummary($year, $period): array
    {
        return SponsorStatistics::generateStatsSummary($year, $period);
    }
}
