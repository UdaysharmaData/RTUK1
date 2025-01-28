<?php

namespace App\Traits\Walletable;

use Str;
use App\Modules\User\Models\User;
use App\Modules\Finance\Models\Wallet;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Enums\AccountTypeEnum;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use App\Modules\Finance\Enums\TransactionTypeEnum;
use App\Modules\Finance\Models\InternalTransaction;
use App\Modules\Finance\Enums\TransactionStatusEnum;
use App\Modules\Finance\Enums\InternalTransactionTypeEnum;

trait HasManyWallets
{
    public function wallets(): MorphMany
    {
        return $this->morphMany(Wallet::class, 'walletable');
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     * 
     * @return void
     */
    public static function bootHasManyWallets(): void
    {
        $model = new static;

        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) { // TODO: Replace with method_exists(static::class, 'bootSoftDeletes')
            static::deleted(function ($model) {
                $model->load('wallets');

                if ($model->wallets->count() > 0) { // Soft Delete the wallets associated with the record. We need this data for accounting purposes even though the resource (model) attached it was permanently deleted
                    $model->wallets->each(function ($item) { $item->delete(); });
                }
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                $model->load('wallets');
                
                if ($model->wallets->count() > 0) { // Soft Delete the wallets associated with the record. We need this data for accounting purposes even though the resource (model) attached it was permanently deleted
                    $model->wallets->each(function ($item) { $item->delete(); });
                }
            });
        }
    }

    /**
     * Credit account
     *
     * @param  mixed  $accountDetail
     * @param  array  $data
     * @return Transaction
     */
    public function creditAccount(array $accountDetail, array $transactionDetail = []): Transaction
    {
        $wallet = $this->wallets()->firstOrCreate([]);

        if ($accountDetail['type'] == AccountTypeEnum::Infinite) {
            $account = $wallet->accounts()->firstOrNew([
                'type' => $accountDetail['type'],
            ]);
            
            $account->balance = $accountDetail['balance'] + $account->balance;
            $account->save();
        } else {
            $account = $wallet->accounts()->create([
                'type' => $accountDetail['type'],
                'balance' => $accountDetail['balance'],
                'valid_from' => $accountDetail['valid_from'],
                'valid_to' => $accountDetail['valid_to'],
            ]);
        }

        return $this->saveInternalTransaction($account, $accountDetail['balance'], $accountDetail['user'], $transactionDetail);
    }

    /**
     * Save internal transaction
     *
     * @param  Account $account
     * @param  float   $amount
     * @param  User    $user
     * @param  array   $transactionDetail
     * @return Transaction
     */
    private function saveInternalTransaction(Account $account, float $amount, User $user, array $transactionDetail = []): Transaction
    {
        $transaction = new Transaction();
        $transaction->transactionable()->associate($account);
        $transaction->user_id = $user->id;
        $transaction->amount = $amount;
        $transaction->type = $transactionDetail['transaction_type'] ?? TransactionTypeEnum::Deposit;
        $transaction->status = TransactionStatusEnum::Completed;
        $transaction->site_id = clientSiteId();
        $transaction->description = $transactionDetail['description'] ?? null;
        $transaction->fee = $transactionDetail['fee'] ?? null;
        $transaction->save();

        $internalTransaction = new InternalTransaction();
        $internalTransaction->account_id = $account->id;
        $internalTransaction->amount = $amount;
        $internalTransaction->type = InternalTransactionTypeEnum::Credit;
        $internalTransaction->transaction_id = $transaction->id;
        $internalTransaction->save();

        return $transaction;
    }
}
