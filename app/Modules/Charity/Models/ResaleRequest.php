<?php

namespace App\Modules\Charity\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Enums\ResaleRequestStateEnum;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InvoiceItemable\HasOneInvoiceItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\InvoiceItemables\CanHaveInvoiceItemableResource;
use App\Modules\Charity\Models\Relations\ResaleRequestRelations;

class ResaleRequest extends Model implements CanHaveInvoiceItemableResource
{
    use HasFactory, ResaleRequestRelations, HasOneInvoiceItem, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'resale_requests';

    protected $fillable = [
        'resale_place_id',
        'charity_id',
        'state',
        'places',
        'unit_price',
        'discount',
        'contact_email',
        'contact_phone',
        'note'
    ];

    protected $casts = [
        'state' => ResaleRequestStateEnum::class
    ];
}
