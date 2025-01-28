<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReferralCode extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = ['user_id', 'code'];

    /**
     * @var string[]
     */
    protected $appends = ['short_code'];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return string
     */
    public function getShortCodeAttribute(): string
    {
        return $this->code;
    }

    /**
     * @return BelongsToMany
     */
    public function referrals(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
