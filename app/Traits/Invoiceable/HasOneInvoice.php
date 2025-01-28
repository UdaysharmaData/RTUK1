<?php

namespace App\Traits\Invoiceable;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasOneInvoice
{
    public function invoice() :MorphOne
    {
        return $this->morphOne(Invoice::class, 'invoiceable');
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     *
     * @return void
     */
    public static function bootHasOneInvoice(): void
    {
        $model = new static;

        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) { // TODO: Replace with method_exists(static::class, 'bootSoftDeletes')
            static::deleted(function ($model) {
                $model->invoice?->delete(); // Delete the invoice associated with the record. We need this data for accounting purposes even though the resource (model) attached it was permanently deleted
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                $model->invoice?->delete(); // Delete the invoice associated with the record. We need this data for accounting purposes even though the resource (model) attached it was permanently deleted
            });
        }
    }
}
