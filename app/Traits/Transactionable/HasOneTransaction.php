<?php

namespace App\Traits\Transactionable;

use App\Modules\Finance\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasOneTransaction
{
    public function transaction() :MorphOne
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     *
     * @return void
     */
    public static function bootHasOneTransaction(): void
    {
        $model = new static;

        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) { // TODO: Replace with method_exists(static::class, 'bootSoftDeletes')
            static::deleted(function ($model) {
                $model->transaction?->delete(); // Delete the transaction associated with the record. We need this data for accounting purposes even though the resource (model) attached it was permanently deleted
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                $model->transaction?->delete(); // Delete the transaction associated with the record. We need this data for accounting purposes even though the resource (model) attached it was permanently deleted
            });
        }
    }
}
