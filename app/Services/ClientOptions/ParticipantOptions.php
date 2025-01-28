<?php

namespace App\Services\ClientOptions;

use Auth;
use App\Http\Helpers\AccountType;
use Illuminate\Support\Facades\Cache;

use App\Enums\EventStateEnum;
use App\Enums\ParticipantStatusEnum;
use App\Enums\ParticipantAddedViaEnum;
use App\Enums\ParticipantsListOrderByFieldsEnum;
use App\Modules\Participant\Models\Participant;

class ParticipantOptions
{
    /**
     * @return mixed
     */
    public static function getYearOptions(): mixed
    {
        $userId = AccountType::isParticipant() ? Auth::user()->id : (AccountType::isCharityOwnerOrCharityUser() ? Auth::user()->id : null);

        return Cache::remember("participants-stats-year-filter-options-{$userId}", now()->addMonth(), function () {
            $years = Participant::query()
                ->filterByAccess()
                ->selectRaw('DISTINCT YEAR(created_at) AS year')
                ->whereNotNull('created_at')
                // ->orderByDesc('created_at')
                ->pluck('year')
                ->sortDesc();

            return $years->map(function ($option, $key) {
                return [
                    'label' => (string) $option,
                    'value' => $option
                ];
            })->values();
        });
    }

    /**
     * @return mixed
     */
    public static function getOrderByOptions(): mixed
    {
        return Cache::remember('participants-list-order-by-filter-options', now()->addHour(), function () {
            return ParticipantsListOrderByFieldsEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getStatusOptions(): mixed
    {
        return Cache::remember('participants-list-status-filter-options', now()->addHour(), function () {
            return ParticipantStatusEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getChannelOptions(): mixed
    {
        return Cache::remember('participants-list-channels-filter-options', now()->addHour(), function () {
            return ParticipantAddedViaEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getStateOptions(): mixed
    {
        return Cache::remember('participants-list-states-filter-options', now()->addHour(), function () {
            return EventStateEnum::_options();
        });
    }
}
