<?php

namespace App\Modules\Charity\Models;

use Carbon\Carbon;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Corporate\Models\Corporate;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Donation extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'donations';

    protected $fillable = [
        'charity_id',
        'corporate_id',
        'amount',
        'conversion_rate',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'date'
    ];

    protected $appends = [
        'status',
        'type'
    ];

    /**
     * Get the charity that owns the donation.
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * Get the corporate that owns the donation.
     * @return BelongsTo
     */
    public function corporate(): BelongsTo
    {
        return $this->belongsTo(Corporate::class);
    }

    /**
     * Get the donation status.
     * 
     * @return bool
     */
    public function getStatusAttribute(): bool
    {
        return $this->expires_at ? Carbon::parse($this->expires_at)->greaterThan(Carbon::now()) : true;
    }

    /**
     * Get the donation type.
     * 
     * @return string|null
     */
    public function getTypeAttribute(): string|null
    {
        return $this->corporate_id ? 'Corporate Donation' : ($this->expires_at ? 'Membership Allocation' : null);
    }

}
