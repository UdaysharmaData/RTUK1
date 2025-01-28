<?php

namespace App\Traits\Transactionable;

use App\Modules\Finance\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasManyTransactions
{
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'transactionable')->latest();
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     * 
     * @return void
     */
    public static function bootHasManyTransactions(): void
    {
        $model = new static;

        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) { // TODO: Replace with method_exists(static::class, 'bootSoftDeletes')
            static::deleted(function ($model) {
                $model->load('transactions');
                
                if ($model->transactions->count() > 0) { // Soft Delete the transactions associated with the record. We need this data for accounting purposes even though the resource (model) attached it was permanently deleted
                    $model->transactions->each(function ($item) { $item->delete(); });
                }
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                $model->load('transactions');
                
                if ($model->transactions->count() > 0) { // Soft Delete the transactions associated with the record. We need this data for accounting purposes even though the resource (model) attached it was permanently deleted
                    $model->transactions->each(function ($item) { $item->delete(); });
                }
            });
        }
    }
}
