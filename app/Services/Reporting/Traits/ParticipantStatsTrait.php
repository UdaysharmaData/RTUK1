<?php

namespace App\Services\Reporting\Traits;

use App\Enums\ParticipantStatusEnum;
use Auth;
use DB;
use App\Services\PercentageChange;
use App\Services\TimePeriodReferenceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HigherOrderWhenProxy;
use App\Http\Helpers\FormatNumber;

use App\Http\Helpers\AccountType;
use App\Modules\Event\Models\EventCategory;
Use App\Modules\Participant\Models\Participant;
use App\Modules\User\Models\User;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\Enums\EventStatisticsTypeEnum;

trait ParticipantStatsTrait
{
    use PercentageChangeTrait, OptionsTrait;

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param string|null $status
     * @param string|null $category
     * @param int|null $month
     * @param int|null $userId
     * @param TimePeriodReferenceService|null $period
     * @return Builder|HigherOrderWhenProxy|mixed|\Illuminate\Database\Query\Builder
     */
    public static function participantsSummaryQuery(?StatisticsEntityEnum $entity = null, ?int $year = null, ?string $status = null, ?string $category = null, ?int $month = null, ?int $userId = null, ?TimePeriodReferenceService $period = null): Builder|HigherOrderWhenProxy|null|\Illuminate\Database\Query\Builder
    {
        return DB::table('participants')
            ->whereNull('deleted_at')
            ->whereIn('participants.event_event_category_id', function ($query) {
                $query->select('id')
                    ->from('event_event_category')
                    ->whereIn('event_event_category.event_category_id', function ($q1) {
                        $q1->select('id')
                            ->from('event_categories')
                            ->whereIn('event_categories.site_id', function ($q2) {
                                $q2->select('id')
                                    ->from('sites')
                                    ->where('sites.id', clientSiteId())
                                    ->when(AccountType::isAdmin(), fn($query) => $query->whereIn('sites.id', function ($q2) {
                                        $q2->select('site_id')
                                            ->from('site_user')
                                            ->where('site_id', clientSiteId())
                                            ->where('user_id', Auth::user()->id);
                                    }));
                            });
                    });
            })->when($userId, fn($query) => $query->where('user_id', '=', $userId))
            ->when($year, fn($query) => $query->whereYear('participants.created_at', '=', $year))
            ->when($period, fn($query) => $query->where('participants.created_at', '>=', $period->toCarbonInstance()))
            ->when($status, fn($query) => $query->where('status', '=', $status))
            ->when($month, fn($query) => $query->whereMonth('participants.created_at', '=', $month))
            ->when($category, fn($query) => $query->whereIn('participants.event_event_category_id', function ($query) use ($category) {
                $query->select('id')
                    ->from('event_event_category')
                    ->whereIn('event_event_category.event_category_id', function ($q1) use ($category) {
                        $q1->select('id')
                            ->from('event_categories')
                            ->where('ref', $category);
                    });
            }));
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param string|null $status
     * @param string|null $category
     * @param int|null $month
     * @param int|null $userId
     * @param TimePeriodReferenceService|null $period
     * @return float
     */
    public static function participantsSummaryPercentChange(?StatisticsEntityEnum $entity = null, ?int $year = null, ?string $status = null,  ?string $category = null, ?int $month = null, ?int $userId = null, ?TimePeriodReferenceService $period = null): float
    {
        $query = self::participantsSummaryQuery($entity, null, $status, $category, $month, $userId);

        if ($year) {
            $previousYear = (string)($year - 1);

            $currentTotalCount = $query->clone()
                ->whereYear('participants.created_at', '=', $year)
                ->count();

            $previousTotalCount = $query->clone()
                ->whereYear('participants.created_at', '=', $previousYear)
                ->count();

            return (new PercentageChange)->calculate($currentTotalCount, $previousTotalCount);

        } elseif ($period) {
            $currentPeriod = $period->toCarbonInstance();
            $previousPeriod = $period->toCarbonInstance(true);
        } else {
            $currentPeriod = now()->subDays(self::getPercentChangeDays());
            $previousPeriod = $currentPeriod->copy()->subDays(self::getPercentChangeDays());
        }

        $currentTotalCount = $query->clone()
            ->where('participants.created_at', '>=', $currentPeriod)
            ->count();

        $previousTotalCount = $query->clone()
            ->where('participants.created_at', '>=', $previousPeriod)
            ->where('participants.created_at', '<', $currentPeriod)
            ->count();

        return (new PercentageChange)->calculate($currentTotalCount, $previousTotalCount);
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param string|null $status
     * @param string|null $category
     * @param int|null $month
     * @param TimePeriodReferenceService|null $period
     * @return \array[][]
     */
    protected static function participantsStatsData(?StatisticsEntityEnum $entity, ?int $year, ?string $status, ?string $category, ?int $month, ?TimePeriodReferenceService $period = null): array
    {
        return [
            'name' => EventStatisticsTypeEnum::Participants->name,
            'total' => FormatNumber::format(self::participantsSummaryQuery($entity, $year, $status, $category, $month, null, $period)->count()),
            'percent_change' => self::participantsSummaryPercentChange($entity, $year, $status, $category, $month, null, $period),
            'type_param_value' => EventStatisticsTypeEnum::Participants->value
        ];
    }

    // /**
    //  * @param StatisticsEntityEnum|null $entity
    //  * @param int|null $year
    //  * @param string|null $status
    //  * @param int|null $month
    //  * @param int|null $userId
    //  * @param TimePeriodReferenceService|null $period
    //  * @return Collection|\Illuminate\Support\Collection|array
    //  */
    // protected static function participantsStackedChartData(?StatisticsEntityEnum $entity = null, ?int $year = null, ?string $status = null, ?int $month = null, ?int $userId = null, ?TimePeriodReferenceService $period = null): Collection|\Illuminate\Support\Collection|array
    // {
    //     return EventCategory::query()
    //         ->whereHas('site', function ($query) {
    //             if (AccountType::isAdmin()) {
    //                 $query->hasAccess();
    //             }
    //             $query->makingRequest();
    //         })->select(['event_categories.name'])
    //         ->selectSub(function (\Illuminate\Database\Query\Builder $query) use ($userId, $status, $year, $month, $period) {
    //             $query->selectRaw('COUNT(*)')
    //                 ->from('participants')
    //                 ->join('event_event_category', 'participants.event_event_category_id', '=', 'event_event_category.id')
    //                 ->whereColumn('event_categories.id', '=', 'event_event_category.event_category_id')
    //                 ->whereNull('participants.deleted_at')
    //                 ->when($userId, fn($query) => $query->where('participants.user_id', '=', $userId))
    //                 ->when($year, fn($query) => $query->whereYear('participants.created_at', '=', $year))
    //                 ->when($status, fn($query) => $query->where('participants.status', '=', $status))
    //                 ->when($month, fn($query) => $query->whereMonth('participants.created_at', '=', $month))
    //                 ->when($period, fn($query) => $query->where('participants.created_at', '>=', $period->toCarbonInstance()));
    //         }, 'total')
    //         ->get()
    //         ->map(function ($category) {
    //             $item = [];
    //             $item['name'] = $category->name;
    //             $item['total'] = $category->total;
    //             return $item;
    //         });
    // }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param string|null $status
     * @param string|null $category
     * @param int|null $month
     * @param int|null $userId
     * @param TimePeriodReferenceService|null $period
     * @return Collection|\Illuminate\Support\Collection|array
     */
    protected static function participantsStackedAreaChartData(?StatisticsEntityEnum $entity = null, ?int $year = null, ?string $status = null, ?string $category = null, ?int $month = null, ?int $userId = null, ?TimePeriodReferenceService $period = null): Collection|\Illuminate\Support\Collection|array
    {
        $statuses = $status ? [ParticipantStatusEnum::tryFrom($status)] : ParticipantStatusEnum::cases();

        return collect($statuses)->map(function ($status) use ($entity, $year, $category, $month, $userId, $period) {
            return [
                'name' => $status->name,
                'total' => self::participantsSummaryQuery($entity, $year, $status->value, $category, $month, $userId, $period)->count()
            ];
        });
    }

    /**
     * @param int $limit
     * @param int|null $year
     * @param string|null $status
     * @return Collection|\Illuminate\Support\Collection|array
     */
    public static function latestParticipants(int $limit = 4, ?int $year = null, ?string $status = null): Collection|\Illuminate\Support\Collection|array
    {
        $site = clientSiteId();
        $cacheKey = "generate_dashboard_latest_participants_{$site}_{$year}_status_{$status}_limit_$limit";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($limit, $year, $status) {
            return self::participantsSummaryQuery(StatisticsEntityEnum::Dashboard, $year, $status, null)
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($participant) {
                    $user = User::find($participant->user_id);
                    $profile = [];
                    $profile['id'] = $user->id;
                    $profile['full_name'] = $user->full_name;
                    $profile['avatar_url'] = $user->profile?->avatar_url;
                    $profile['created_at'] = $user->created_at;

                    return $profile;
                });
        });
    }
}
