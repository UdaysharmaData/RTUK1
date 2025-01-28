<?php

namespace App\Services\PasswordProtectionPolicy\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface KeepPasswordHistory
{
    /**
     * @return HasMany
     */
    public function passwordRecords(): HasMany;

    /**
     * @return void
     */
    public function deletePasswordHistory(): void;
}
