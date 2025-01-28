<?php

namespace App\Modules\Charity\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Enums\EventPlaceInvoicePeriodEnum;
use App\Enums\EventPlaceInvoiceStatusEnum;
use App\Traits\InvoiceItemable\HasOneInvoiceItem;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\InvoiceItemables\CanHaveInvoiceItemableResource;

class EventPlaceInvoice extends Model implements CanHaveInvoiceItemableResource
{
    use HasFactory, HasOneInvoiceItem, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'event_place_invoices';

    protected $fillable = [
        'charity_id',
        'year',
        'period',
        'status',
        'invoice_sent_on'
    ];

    protected $casts = [
        // 'period' => EventPlaceInvoicePeriodEnum::class,
        'status' => EventPlaceInvoiceStatusEnum::class,
        'invoice_sent_on' => 'datetime'
    ];

    /**
     * Get the charity.
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }
}
