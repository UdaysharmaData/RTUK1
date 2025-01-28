<?php

namespace App\Services\Reporting\Traits;

use Carbon\Carbon;
use App\Http\Helpers\FormatNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HigherOrderWhenProxy;

use App\Modules\Partner\Models\Partner;
use App\Services\Reporting\Enums\StatisticsEntityEnum;

trait PartnerStatsTrait
{
    /**
     * @return Builder|HigherOrderWhenProxy|mixed
     */
    public static function partnersSummaryQuery(): Builder|HigherOrderWhenProxy|null
    {
        return Partner::query()
            ->whereHas('site', function ($query) {
                $query->makingRequest();
            });
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param Carbon|null $period
     * @return \array[][]
     */
    protected static function partnersStatsData(): array
    {
        return [
            'name' => 'Partners',
            'total' => FormatNumber::format(self::partnersSummaryQuery()->count()),
            'type_param_value' => 'partners'
        ];
    }
}
