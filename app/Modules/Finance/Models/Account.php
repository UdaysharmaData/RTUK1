<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Finance\Enums\AccountTypeEnum;
use App\Modules\Finance\Enums\AccountStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\Finance\Models\Relations\AccountRelations;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\FilterableListQueryScope;

class Account extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory,
        SoftDeletes,
        AccountRelations,
        AddUuidRefAttribute,
        UuidRouteKeyNameTrait,
        FilterableListQueryScope;

    protected $table = 'accounts';

    protected $fillable = [
        'wallet_id',
        'status',
        'type',
        'name',
        'balance',
        'valid_from',
        'valid_to'
    ];

    protected $casts = [
        'status' => AccountStatusEnum::class,
        'type' => AccountTypeEnum::class,
    ];

    protected $appends = [

    ];

    // public static $actionMessages = [
    //     'force_delete' => 'Deleting the payment(s) permanently will unlink it from invoices. This action is irreversible.'
    // ];

}
