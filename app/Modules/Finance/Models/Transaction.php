<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Finance\Models\Relations\TransactionRelations;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;

use App\Modules\Finance\Enums\TransactionTypeEnum;
use App\Modules\Finance\Enums\TransactionStatusEnum;
use App\Modules\Finance\Enums\TransactionPaymentMethodEnum;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\SiteIdAttributeGenerator;
use App\Traits\FilterableListQueryScope;

class Transaction extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory,
        SoftDeletes,
        TransactionRelations,
        AddUuidRefAttribute,
        UuidRouteKeyNameTrait,
        SiteIdAttributeGenerator,
        FilterableListQueryScope;

    protected $table = 'transactions';

    protected $fillable = [
        'user_id',
        'transactionable_id',
        'transactionable_type',
        'ongoing_external_transaction_id',
        'email',
        'amount',
        'status',
        'type',
        'fee',
        'payment_method',
        'description'
    ];

    protected $casts = [
        'status' => TransactionStatusEnum::class,
        'type' => TransactionTypeEnum::class,
        'payment_method' => TransactionPaymentMethodEnum::class,
    ];

    protected $appends = [

    ];

    public static $actionMessages = [
        'force_delete' => 'Deleting the payment(s) permanently will unlink it from invoices, invoice items and others. This action is irreversible.'
    ];

}
