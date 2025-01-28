<?php

namespace App\Modules\User\Models;

use App\Modules\Corporate\Models\Corporate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Deposit extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'corporate_id',
        'user_id',
        'amount',
        'refund',
        'conversion_rate',
        'stripe_id'
    ];

    /**
     * May belong to a corporate
     * @return BelongsTo
     */
    public function corporate(): BelongsTo
    {
        return $this->belongsTo(Corporate::class);
    }

    /**
     * Ability to grab the original depositer (multi user corporate account)
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
