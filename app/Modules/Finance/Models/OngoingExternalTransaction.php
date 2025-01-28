<?php

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Enums\OngoingExternalTransactionStateEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\Finance\Enums\OngoingExternalTransactionStatusEnum;
use App\Modules\Finance\Models\Relations\OngoingExternalTransactionRelations;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\SiteIdAttributeGenerator;
use App\Traits\FilterableListQueryScope;

class OngoingExternalTransaction extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory,
        SoftDeletes,
        AddUuidRefAttribute,
        UuidRouteKeyNameTrait,
        OngoingExternalTransactionRelations,
        SiteIdAttributeGenerator,
        FilterableListQueryScope;

    protected $table = 'ongoing_external_transactions';

    protected $fillable = [
        'user_id',
        'payment_intent_id',
        'email',
        'amount',
        'status',
        'state',
        'payload',
        'description',
        'response'
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'status' => OngoingExternalTransactionStatusEnum::class,
        'state' => OngoingExternalTransactionStateEnum::class,
    ];

    protected $appends = [

    ];

    public static $actionMessages = [
        'force_delete' => 'Deleting the payment(s) permanently will unlink it from invoices. This action is irreversible.'
    ];

}
