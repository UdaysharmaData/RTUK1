<?php

namespace App\Services\PasswordProtectionPolicy\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordRecord extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = ['password', 'expires_at'];

    /**
     * @var string[]
     */
    protected $casts = [
        'expires_at' => 'datetime'
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * check if referenced password is expired
     * @return bool
     */
    public function hasExpired(): bool
    {
        return now() >= $this->expires_at;
    }
}
