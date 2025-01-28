<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Finance\Enums\AccountTypeEnum;
use App\Modules\Finance\Enums\AccountStatusEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\Finance\Models\Relations\WalletRelations;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\FilterableListQueryScope;

class Wallet extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory,
        SoftDeletes,
        WalletRelations,
        AddUuidRefAttribute,
        UuidRouteKeyNameTrait,
        FilterableListQueryScope;

    protected $table = 'wallets';

    protected $fillable = [
        'walletable_id',
        'walletable_type',
    ];

    protected $casts = [

    ];

    protected $appends = [
        'balance'
    ];

    // public static $actionMessages = [
    //     'force_delete' => 'Deleting the payment(s) permanently will unlink it from invoices. This action is irreversible.'
    // ];

    /**
     * Get the balance of the wallet
     *
     * @return Attribute
     */
    protected function balance(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->accounts()
                    ->where('type', AccountTypeEnum::Infinite)
                    ->first()?->balance + $this->finiteAccountsBalance;
            },
        );
    }

    /**
     * Get the balance of the finite accounts (sum of the related active accounts)
     *
     * @return Attribute
     */
    protected function finiteAccountsBalance(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->accounts()
                    ->where('type', AccountTypeEnum::Finite)
                    ->where('status', AccountStatusEnum::Active) // TODO: @tsaffi: Write a command that runs daily and updates the status of the accounts. Set them active when valid_from is today (and valid_to is in the future) and inactive when valid_to is before today
                    ->sum('balance');
            },
        );
    }
}
