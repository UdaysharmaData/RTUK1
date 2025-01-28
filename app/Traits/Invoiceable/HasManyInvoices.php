<?php

namespace App\Traits\Invoiceable;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasManyInvoices
{
    public function invoices(): MorphMany
    {
        return $this->morphMany(Invoice::class, 'invoiceable');
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     * 
     * @return void
     */
    public static function bootHasManyInvoices(): void
    {
        $model = new static;

        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) { // TODO: Replace with method_exists(static::class, 'bootSoftDeletes')
            static::deleted(function ($model) {
                $model->load('invoices');
                
                if ($model->invoices->count() > 0) { // Soft Delete the invoices associated with the record. We need this data for accounting purposes even though the resource (model) attached it was permanently deleted
                    $model->invoices->each(function ($item) { $item->delete(); });
                }
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                $model->load('invoices');
                
                if ($model->invoices->count() > 0) { // Soft Delete the invoices associated with the record. We need this data for accounting purposes even though the resource (model) attached it was permanently deleted
                    $model->invoices->each(function ($item) { $item->delete(); });
                }
            });
        }
    }
}
