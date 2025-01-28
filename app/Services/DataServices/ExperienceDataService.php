<?php

namespace App\Services\DataServices;

use App\Models\Experience;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\Reporting\ExperienceStatistics;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ExperienceDataService extends DataService implements DataServiceInterface
{
    public function __construct()
    {
        $this->builder = Experience::query();
        $this->appendAnalyticsData = false;
    }

    /**
     * @param mixed $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder
    {

    }

    /**
     * @param mixed $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedList(mixed $request): LengthAwarePaginator
    {

    }

    /**
     * @param mixed $request
     * @return Builder[]|Collection
     */
    public function getExportList(mixed $request): Builder|Collection
    {

    }

    /**
     * @param $year
     * @param $period
     * @return array
     */
    public function generateStatsSummary($year, $period): array
    {
        return ExperienceStatistics::generateStatsSummary($year, $period);
    }
}
