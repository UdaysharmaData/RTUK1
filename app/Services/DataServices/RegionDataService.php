<?php

namespace App\Services\DataServices;

use App\Models\Region;
use App\Services\ConfigurableEventPropertyService;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\DataServices\Traits\ConfigurableEventPropertyServiceTrait;
use App\Services\Reporting\RegionStatistics;

class RegionDataService implements DataServiceInterface
{
    use ConfigurableEventPropertyServiceTrait;

    /**
     * @var ConfigurableEventPropertyService
     */
    private ConfigurableEventPropertyService $eventPropertyService;

    public function __construct()
    {
        $this->eventPropertyService = new ConfigurableEventPropertyService(Region::query(), ['faqs', 'meta', 'site', 'image', 'gallery']);
    }

    /**
     * @param $year
     * @param $period
     * @return array
     */
    public function generateStatsSummary($year, $period): array
    {
        return RegionStatistics::generateStatsSummary($year, $period);
    }
}
