<?php

namespace App\Services\PasswordProtectionPolicy\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passphrase extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = ['challenge', 'response'];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
