<?php

namespace App\Services\ClientOptions;

use Auth;
use Carbon\Carbon;
use App\Enums\RoleNameEnum;
use App\Enums\EventStateEnum;
use App\Http\Helpers\AccountType;
use App\Modules\Event\Models\Event;
use Illuminate\Support\Facades\Cache;
use App\Modules\Event\Models\EventEventCategory;

class PartnerEventOptions
{
    /**
     * @return mixed
     */
    public static function getYearOptions(): mixed
    {
        // TODO: @tsaffi: Write a reusable trait to get this unique value and use them for reporting caching too. Do well to consider the site_id too
        // Also ensure to link this to the entity cache data service and have this cache cleared once the start_date of the event is updated.

        if (Auth::user()->activeRole?->role?->name == RoleNameEnum::Charity) {
            $unique = Auth::user()->activeRole?->role?->name?->name . ' - ' . Auth::user()->id;
        } else {
            $unique = Auth::user()->activeRole?->role?->name?->name;
        }

        return Cache::remember("partner-events-list-year-filter-options-{$unique}", now()->addHour(), function () {
            $years = EventEventCategory::selectRaw('DISTINCT YEAR(start_date) AS year')
                ->whereHas('eventCategory', function ($query) {
                    $query->where('site_id', clientSiteId());
                })->whereHas('event', function ($query) {
                    $query->partnerEvent(Event::ACTIVE)
                        ->estimated(Event::INACTIVE)
                        ->where('status', Event::ACTIVE)
                        ->when(
                            AccountType::isParticipant(),
                            fn ($query) => $query->state(EventStateEnum::Live)
                        );
                })->whereNotNull('start_date')
                // ->orderByDesc('start_date')
                ->when(
                    AccountType::isParticipant(),
                    fn ($query) => $query->where(function ($query) {
                            $query->whereNull('registration_deadline')
                                ->orWhere('registration_deadline', '>=', Carbon::now());
                        })
                )->pluck('year')
                ->sortDesc();

            return $years->map(function ($option, $key) {
                return [
                    'label' => (string) $option,
                    'value' => $option
                ];
            })->values();
        });
    }
}