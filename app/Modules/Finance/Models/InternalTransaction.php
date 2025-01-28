<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Finance\Enums\InternalTransactionTypeEnum;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\Finance\Models\Relations\InternalTransactionRelations;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\FilterableListQueryScope;

class InternalTransaction extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory,
        SoftDeletes,
        AddUuidRefAttribute,
        UuidRouteKeyNameTrait,
        InternalTransactionRelations,
        FilterableListQueryScope;

    protected $table = 'internal_transactions';

    protected $fillable = [
        'account_id',
        'transaction_id',
        'amount',
        'type'
    ];

    protected $casts = [
        'type' => InternalTransactionTypeEnum::class,
    ];

    protected $appends = [

    ];

    // public static $actionMessages = [
    //     'force_delete' => 'Deleting the payment(s) permanently will unlink it from invoices. This action is irreversible.'
    // ];

}
