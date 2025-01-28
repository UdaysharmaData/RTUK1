<?php

namespace App\Services\PasswordProtectionPolicy\Observers;

use App\Services\PasswordProtectionPolicy\Contracts\KeepPasswordHistory;

class PasswordProtectableObserver
{
    /**
     * @var string
     */
    protected string $column;

    public function __construct()
    {
        $this->column = config('passwordprotectionpolicy.observe.column') ?: 'password';
    }

//    /**
//     * Handle the User "creating" event.
//     *
//     * @param KeepPasswordHistory $keepPasswordHistory
//     * @return void
//     */
//    public function creating(KeepPasswordHistory $keepPasswordHistory)
//    {
//
//    }

    /**
     * Handle the User "created" event.
     *
     * @param KeepPasswordHistory $keepPasswordHistory
     * @return void
     */
    public function created(KeepPasswordHistory $keepPasswordHistory)
    {
        $this->updatePasswordHistory($keepPasswordHistory);
    }

    /**
     * update password policy if password was changed after a model update event
     * Handle the User "updated" event.
     *
     * @param KeepPasswordHistory $keepPasswordHistory
     * @return void
     */
    public function updated(KeepPasswordHistory $keepPasswordHistory)
    {
        if ($keepPasswordHistory->wasChanged($this->column)) {
            $this->updatePasswordHistory($keepPasswordHistory);
        }
    }

    /**
     * @param KeepPasswordHistory $keepPasswordHistory
     * @return void
     */
    protected function updatePasswordHistory(KeepPasswordHistory $keepPasswordHistory): void
    {
        if (
            (new \ReflectionClass($keepPasswordHistory))
                ->implementsInterface(KeepPasswordHistory::class)
            && isset($keepPasswordHistory->password)
        ) {
            $keepPasswordHistory->passwordRecords()->create([
                $this->column => $keepPasswordHistory->{$this->column},
                'expires_at' => now()->addDays($keepPasswordHistory->getPasswordAgeForRole())
            ]);
        }
    }
}
