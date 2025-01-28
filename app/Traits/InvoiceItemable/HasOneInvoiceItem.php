<?php

namespace App\Traits\InvoiceItemable;

use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasOneInvoiceItem
{
    public function invoiceItem() :MorphOne
    {
        return $this->morphOne(InvoiceItem::class, 'invoice_itemable');
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     *
     * @return void
     */
    public static function bootHasOneInvoiceItem(): void
    {
        $model = new static;

        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) { // TODO: Replace with method_exists(static::class, 'bootSoftDeletes')
            static::deleted(function ($model) {
                $model->invoiceItem?->delete(); // Delete the invoice item associated with the record
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                $model->invoiceItem?->delete(); // Delete the invoice item associated with the record
            });
        }
    }
}
