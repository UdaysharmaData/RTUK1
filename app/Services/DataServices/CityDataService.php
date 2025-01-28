<?php

namespace App\Services\DataServices;

use App\Models\City;
use App\Services\ConfigurableEventPropertyService;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\DataServices\Traits\ConfigurableEventPropertyServiceTrait;
use App\Services\Reporting\CityStatistics;

class CityDataService implements DataServiceInterface
{
    use ConfigurableEventPropertyServiceTrait;

    /**
     * @var ConfigurableEventPropertyService
     */
    private ConfigurableEventPropertyService $eventPropertyService;

    public function __construct()
    {
        $this->eventPropertyService = new ConfigurableEventPropertyService(City::query(), ['faqs', 'meta', 'site', 'region', 'image', 'gallery']);
    }

    /**
     * @param $year
     * @param $period
     * @return array
     */
    public function generateStatsSummary($year, $period): array
    {
        return CityStatistics::generateStatsSummary($year, $period);
    }
}
