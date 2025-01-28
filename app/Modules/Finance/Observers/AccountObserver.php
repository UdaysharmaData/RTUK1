<?php
namespace App\Modules\Finance\Observers;

use App\Modules\Finance\Models\Account;

class AccountObserver
{
    protected $afterCommit = true;

    /**
     * Handle the Account "created" event.
     *
     * @param  Account  $account
     * @return void
     */
    public function created(Account $account)
    {
        $account->name = generateAccountName($account->wallet, $account, $account->type);
        $account->save();
    }

    /**
     * Handle the Account "updated" event.
     *
     * @param  Account  $account
     * @return void
     */
    public function updated(Account $account)
    {
        //
    }

    /**
     * Handle the Account "deleted" event.
     *
     * @param  Account  $account
     * @return void
     */
    public function deleted(Account $account)
    {
        //
    }

    /**
     * Handle the Account "restored" event.
     *
     * @param  Account  $account
     * @return void
     */
    public function restored(Account $account)
    {
        //
    }

    /**
     * Handle the Account "force deleted" event.
     *
     * @param  Account  $account
     * @return void
     */
    public function forceDeleted(Account $account)
    {
        //
    }
}