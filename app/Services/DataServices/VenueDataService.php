<?php

namespace App\Services\DataServices;

use App\Models\Venue;
use App\Services\ConfigurableEventPropertyService;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\DataServices\Traits\ConfigurableEventPropertyServiceTrait;
use App\Services\Reporting\VenueStatistics;

class VenueDataService implements DataServiceInterface
{
    use ConfigurableEventPropertyServiceTrait;

    /**
     * @var ConfigurableEventPropertyService
     */
    private ConfigurableEventPropertyService $eventPropertyService;

    public function __construct()
    {
        $this->eventPropertyService = new ConfigurableEventPropertyService(Venue::query(), ['faqs', 'meta', 'site', 'image', 'gallery', 'city.region']);
    }

    /**
     * @param $year
     * @param $period
     * @return array
     */
    public function generateStatsSummary($year, $period): array
    {
        return VenueStatistics::generateStatsSummary($year, $period);
    }
}
