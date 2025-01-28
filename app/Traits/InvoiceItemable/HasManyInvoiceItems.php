<?php

namespace App\Traits\InvoiceItemable;

use App\Enums\InvoiceItemStatusEnum;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasManyInvoiceItems
{
    public function invoiceItems(): MorphMany
    {
        return $this->morphMany(InvoiceItem::class, 'invoice_itemable');
    }

    /**
     * Get the first invoice item created (mostly for participants as the second invoice item only gets created during a transfer)
     *
     * @return MorphOne
     */
    public function invoiceItem(): MorphOne
    {
        return $this->morphOne(InvoiceItem::class, 'invoice_itemable')->oldest('id');
    }

    /**
     * Get the transferred invoice items (a participant record can be transferred once). NB: Ensure the status of the participant can't be changed once it is set to transferred.
     *
     * @return MorphOne
     */
    public function transferredInvoiceItem(): MorphOne
    {
        return $this->morphOne(InvoiceItem::class, 'invoice_itemable')->where('status', InvoiceItemStatusEnum::Transferred)->latest('id');
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     * 
     * @return void
     */
    public static function bootHasManyInvoiceItems(): void
    {
        $model = new static;

        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) { // TODO: Replace with method_exists(static::class, 'bootSoftDeletes')
            static::deleted(function ($model) {
                $model->load('invoiceItems');

                if ($model->invoiceItems->count() > 0) { // Delete the invoice items associated with the record
                    $model->invoiceItems->each(function ($item) { $item->delete(); });
                }
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                $model->load('invoiceItems');
                
                if ($model->invoiceItems->count() > 0) { // Delete the invoice items associated with the record
                    $model->invoiceItems->each(function ($item) { $item->delete(); });
                }
            });
        }
    }
}
