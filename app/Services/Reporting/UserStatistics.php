<?php

namespace App\Services\Reporting;

use App\Enums\MonthEnum;
use App\Enums\RoleNameEnum;
use App\Enums\TimeReferenceEnum;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\User;
use App\Services\Charting\StackedColumnChart;
use App\Services\PercentageChange;
use App\Http\Helpers\FormatNumber;
use App\Services\TimePeriodReferenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HigherOrderWhenProxy;
use App\Services\Reporting\Traits\StructureChartDataTrait;

class UserStatistics
{
    /**
     * @var Builder
     */
    public Builder $builder;

    /**
     * @var int|null
     */
    private ?int $siteId;

    /**
     * @var int|null
     */
    private ?int $percentChangeDays;

    /**
     * @var Carbon
     */
    private Carbon $cachePeriod;

    /**
     * @var Carbon
     */
    private Carbon $percentChangePeriodInCarbonInstance;

    /**
     * @var TimePeriodReferenceService|null
     */
    private ?TimePeriodReferenceService $timePeriodReferenceService = null;

    /**
     * @var bool
     */
    private bool $applyDefaultPeriod = false;

    public function __construct()
    {
        $this->builder = User::query()->currentSiteOnly();
        $this->siteId = clientSiteId();
        $siteCode = clientSiteCode();
        $this->percentChangeDays = config("apiclient.$siteCode.percent_change_days", 7);
        $this->percentChangePeriodInCarbonInstance = now()->subDays($this->percentChangeDays);
        $this->cachePeriod = now()->addHour();
    }

    /**
     * @param int $days
     * @return UserStatistics
     */
    private function setPercentChangeDays(int $days): UserStatistics
    {
        $this->percentChangeDays = $days;

        return $this;
    }

    /**
     * @param Carbon $period
     * @return UserStatistics
     */
    private function setPercentChangePeriod(Carbon $period): UserStatistics
    {
        $this->percentChangePeriodInCarbonInstance = $period;

        return $this;
    }

    /**
     * @param Carbon $period
     * @return UserStatistics
     */
    private function setCachePeriod(Carbon $period): UserStatistics
    {
        $this->cachePeriod = $period;

        return $this;
    }

    /**
     * @param Builder $builder
     * @return $this
     */
    public function setBuilder(Builder $builder): UserStatistics
    {
        $this->builder = $builder;

        return $this;
    }

    /**
     * @return array
     */
    protected function months(): array
    {
        return MonthEnum::options();
    }

    /**
     * @return Collection|\Illuminate\Support\Collection|array
     */
    public function summary(): Collection|\Illuminate\Support\Collection|array
    {
        $year = request('year');
        $previousYear = $year ? (string)($year - 1) : null;
        $month = request('month');
        $roleName = request('role');
        $period = TimeReferenceEnum::tryFrom($requestPeriod = request('period'))?->value;

        if ($period) {
            $this->timePeriodReferenceService = new TimePeriodReferenceService($period);
        }

        $periodInCarbonInstance = $period
            ? $this->timePeriodReferenceService->toCarbonInstance()
            : null;
        $previousPeriodInCarbonInstance = $period
            ? $this->timePeriodReferenceService->toCarbonInstance(true)
            : null;

        if ($roleName === 'all') {
            return collect([
                [
                    'name' => 'Users',
                    'total' => $this->getUsersQuery($year, $month, $periodInCarbonInstance)->count(),
                    'role_param_value' => 'all',
                    'percent_change' => (new PercentageChange)->calculate(
                        $this->getUsersQuery(
                            $year,
                            $month,
                            $periodInCarbonInstance ?? $this->defaultChangePeriodInstance(),
                            true
                        )->count(),
                        $this->getUsersQuery(
                            $previousYear,
                            $month,
                            $previousPeriodInCarbonInstance ?? $this->defaultChangePeriodInstance(true),
                            true,
                            true,
                        )->count(),
                    )
                ]
            ]);
        }

        $roleName = RoleNameEnum::tryFrom($roleName)?->value;

        $data = $this->getRolesSelectSubUsersQuery($year, $month, $periodInCarbonInstance, $roleName)
            ->get()
            ->map(function ($value) use ($previousYear, $periodInCarbonInstance, $previousPeriodInCarbonInstance, $month, $year) {
                return [
                    'name' => $this->formatLabel($role = $value['name']->value),
                    'total' => FormatNumber::format($value['total']),
                    'role_param_value' => $role,
                    'percent_change' => (new PercentageChange)->calculate(
                        $this->getUsersWhereHasRoleQuery(
                            $role,
                            $year,
                            $month,
                            $periodInCarbonInstance ?? $this->defaultChangePeriodInstance(),
                            true
                        )->count(),
                        $this->getUsersWhereHasRoleQuery(
                            $role,
                            $previousYear,
                            $month,
                            $previousPeriodInCarbonInstance ?? $this->defaultChangePeriodInstance(true),
                            true,
                            true
                        )->count()
                    )
                ];
            });

        return is_null($roleName) ? $data->prepend([
            'name' => 'Users',
            'total' => $this->getUsersQuery($year, $month, $periodInCarbonInstance)->count(),
            'role_param_value' => 'all',
            'percent_change' => (new PercentageChange)->calculate(
                $this->getUsersQuery(
                    $year,
                    $month,
                    $periodInCarbonInstance ?? $this->defaultChangePeriodInstance(),
                    true
                )->count(),
                $this->getUsersQuery(
                    $previousYear,
                    $month,
                    $previousPeriodInCarbonInstance ?? $this->defaultChangePeriodInstance(true),
                    true,
                    true,
                )->count(),
            )
        ]) : $data;
    }

    /**
     * @return array
     */
    public function chart(): array
    {
        $year = request('year');
        $role = request('role');
        $period = TimeReferenceEnum::tryFrom($requestPeriod = request('period'))?->value;
        $cacheKey = "generate_user_registration_stats_chart_{$year}_period_{$requestPeriod}_{$role}_$this->siteId";

        if ($period) {
            $this->timePeriodReferenceService = new TimePeriodReferenceService($period);
            $period = $this->timePeriodReferenceService->toCarbonInstance($period);
        }

        return Cache::remember(
            $cacheKey,
            $this->cachePeriod,
            function () use ($period, $year, $role) {
                $data = [];

                foreach ($this->months() as $key => $month) {
                    $data[] = [
                        'categories' => $this->getRolesSelectSubUsersQuery($year, $month, $period, $role)
                            ->get()
                            ->map(function ($value) use ($key) {
                                return [
                                    'name' => $this->formatLabel($value['name']->value),
                                    'total' => $value['total'],
                                ];
                            }),
                        'month' => $key
                    ];
                }

                return (new StackedColumnChart)->format($data);
            }
        );
    }

    /**
     * @param string|null $year
     * @param string|null $month
     * @param Carbon|null $period
     * @param string|null $role
     * @return Builder|HigherOrderWhenProxy|mixed
     */
    protected function getRolesSelectSubUsersQuery(
        ?string $year = null,
        ?string $month = null,
        ?Carbon $period = null,
        ?string $role = null
    ): mixed
    {
        return Role::query()
            ->siteOnly()
            ->select(['roles.name'])
            ->when(is_null($name = RoleNameEnum::tryFrom($role)?->value) || $role === 'all',
                fn(Builder $query) => $query,
                fn(Builder $query) => $query->where('roles.name', '=', $name)
            )
            ->selectSub(function ($query) use ($period, $month, $year) {
                $query->selectRaw('COUNT(*)')
                    ->from('users')
                    ->join('role_user', 'users.id', '=', 'role_user.user_id')
                    ->join('site_user', function (JoinClause $join) {
                        $join->on('users.id', '=', 'site_user.user_id')
                            ->where('site_user.site_id', '=', clientSiteId());
                    })
                    ->whereColumn('roles.id', '=', 'role_user.role_id')
                    ->whereNull('users.deleted_at')
                    ->when($year, fn($query) => $query->whereYear('users.created_at', '=', $year))
                    ->when($month, fn($query) => $query->whereMonth('users.created_at', '=', $month))
                    ->when($period, fn($query) => $query->where('users.created_at', '>=', $period));
            }, 'total');
    }

    /**
     * @param string|null $role
     * @param string|null $year
     * @param string|null $month
     * @param Carbon|null $period
     * @param bool $isPercentageChange
     * @param bool $isPreviousPeriod
     * @return Builder
     */
    protected function getUsersWhereHasRoleQuery(
        ?string $role = null,
        ?string $year = null,
        ?string $month = null,
        ?Carbon $period = null,
        bool $isPercentageChange = false,
        bool $isPreviousPeriod = false
    ): Builder
    {
        return User::query()
            ->currentSiteOnly()
            ->whereHas('roles', function ($query) use ($isPreviousPeriod, $isPercentageChange, $role, $period, $month, $year) {
                $query->whereNull('users.deleted_at')
                    ->when(is_null($name = RoleNameEnum::tryFrom($role)?->value) || $role === 'all',
                        fn(Builder $query) => $query,
                        fn(Builder $query) => $query->where('roles.name', '=', $name)
                    )
                    ->when($month, fn($query) => $query->whereMonth('users.created_at', '=', $month))
                    ->when(
                        $isPercentageChange,
                        $this->periodFilterClosure($isPreviousPeriod, $period, $year),
                        function (Builder $query) use ($period, $year) {
                            $query->when($period, fn($query) => $query->where('users.created_at', '>=', $period))
                            ->when($year, fn($query) => $query->whereYear('users.created_at', '=', $year));
                        }
                    );
            });
    }

    /**
     * @param string|null $year
     * @param string|null $month
     * @param Carbon|null $period
     * @param bool $isPercentageChange
     * @param bool $isPreviousPeriod
     * @param bool $withoutRoles
     * @return Builder|HigherOrderWhenProxy|mixed
     */
    protected function getUsersQuery(
        ?string $year = null,
        ?string $month = null,
        ?Carbon $period = null,
        bool $isPercentageChange = false,
        bool $isPreviousPeriod = false,
        bool $withoutRoles = false
    ): mixed
    {
        return User::query()
            ->currentSiteOnly()
            ->when($withoutRoles, fn($query) => $query->doesntHave('roles'))
            ->when($month, fn(Builder $query) => $query->whereMonth('created_at', '=', $month))
            ->when(
                $isPercentageChange,
                $this->periodFilterClosure($isPreviousPeriod, $period, $year),
                function (Builder $query) use ($period, $year) {
                    $query->when($period, fn(Builder $query) => $query->where('users.created_at', '>=', $period))
                        ->when($year, fn(Builder $query) => $query->whereYear('users.created_at', '=', $year));
                }
            );
    }

    /**
     * @param bool $isPreviousPeriod
     * @param Carbon|null $period
     * @param string|null $year
     * @return \Closure
     */
    function periodFilterClosure(bool $isPreviousPeriod, ?Carbon $period, ?string $year): \Closure
    {
        return function (Builder $query) use ($isPreviousPeriod, $period, $year) {
            if ($year) {
                $query->whereYear('users.created_at', '=', $year);
            } elseif ($period) {
                if ($this->applyDefaultPeriod) {
                    $originalPeriod = $this->percentChangePeriodInCarbonInstance;
                } else {
                    $originalPeriod = $this->timePeriodReferenceService->toCarbonInstance();
                }

                $query->when(
                    $isPreviousPeriod,
                    function (Builder $query) use ($period, $originalPeriod) {
                        $query->where('users.created_at', '>=', $period)
                            ->where('users.created_at', '<', $originalPeriod);
                    },
                    function (Builder $query) use ($period) {
                        $query->where('users.created_at', '>=', $period);
                    }
                );
            }
        };
    }

    /**
     * @param string $roleName
     * @return string
     */
    public function formatLabel(string $roleName): string
    {
        return ucwords(str_replace('_', ' ', $roleName));
    }

    /**
     * @param bool $isPrevious
     * @return Carbon
     */
    private function defaultChangePeriodInstance(bool $isPrevious = false): Carbon
    {
        $this->applyDefaultPeriod = true;

        if ($isPrevious) {
            return $this->percentChangePeriodInCarbonInstance->subDays($this->percentChangeDays);
        }

        return $this->percentChangePeriodInCarbonInstance;
    }
}
