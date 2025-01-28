<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\Finance\Models\Relations\ExternalTransactionRelations;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\FilterableListQueryScope;

class ExternalTransaction extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory,
        SoftDeletes,
        AddUuidRefAttribute,
        UuidRouteKeyNameTrait,
        ExternalTransactionRelations,
        FilterableListQueryScope;

    protected $table = 'external_transactions';

    protected $fillable = [
        'transaction_id',
        'payment_intent_id',
        'charge_id',
        'refund_id',
        'payload'
    ];

    protected $casts = [
        'payload' => 'array'
    ];

    protected $appends = [

    ];

    // public static $actionMessages = [
    //     'force_delete' => 'Deleting the payment(s) permanently will unlink it from invoices. This action is irreversible.'
    // ];

}
