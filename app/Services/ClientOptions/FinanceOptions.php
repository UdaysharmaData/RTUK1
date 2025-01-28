<?php

namespace App\Services\ClientOptions;

use Auth;
use Illuminate\Support\Facades\Cache;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Enums\AccountTypeEnum;
use App\Modules\Finance\Enums\AccountStatusEnum;
use App\Modules\User\Models\ParticipantProfile;
use App\Enums\InternalTransactionsListOrderByFieldsEnum;

class FinanceOptions
{
    /**
     * @return mixed
     */
    public static function getAccountOptions(): mixed
    {
        return Cache::remember('finance-accounts-options', now()->addHour(), function () {
            return Account::whereHas('wallet', function ($query) {
                $query->whereHasMorph( // TODO: @tsaffi - Improve on this to handle return the balance of different entities like charities, etc
                    'walletable',
                    [ParticipantProfile::class],
                    function ($query) {
                        $query->whereHas('profile', function ($query) {
                            $query->where('user_id', Auth::user()->id);
                        });
                    }
                );
            })->select('id', 'type', 'name')->get();
        });
    }

    /**
     * @return mixed
     */
    public static function getInternalTransactionsOrderByOptions(): mixed
    {
        return Cache::remember('finance-internal-transactions-list-order-by-filter-options', now()->addHour(), function () {
            return InternalTransactionsListOrderByFieldsEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getAccountStatusOptions(): mixed
    {
        return Cache::remember('finance-accounts-list-status-filter-options', now()->addHour(), function () {
            return AccountStatusEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getAccountTypeOptions(): mixed
    {
        return Cache::remember('finance-accounts-list-type-filter-options', now()->addHour(), function () {
            return AccountTypeEnum::_options();
        });
    }
}
