<?php

namespace App\Traits\Walletable;

use App\Modules\Finance\Models\Wallet;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasOneWallet
{
    public function wallet() :MorphOne
    {
        return $this->morphOne(Wallet::class, 'walletable');
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     *
     * @return void
     */
    public static function bootHasOneWallet(): void
    {
        $model = new static;

        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) { // TODO: Replace with method_exists(static::class, 'bootSoftDeletes')
            static::deleted(function ($model) {
                $model->wallet?->delete(); // Delete the wallet associated with the record. We need this data for accounting purposes even though the resource (model) attached it was permanently deleted
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                $model->wallet?->delete(); // Delete the wallet associated with the record. We need this data for accounting purposes even though the resource (model) attached it was permanently deleted
            });
        }
    }
}
