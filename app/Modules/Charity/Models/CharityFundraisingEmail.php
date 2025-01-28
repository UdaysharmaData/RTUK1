<?php

namespace App\Modules\Charity\Models;

use App\Modules\Event\Models\Event;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Replaces the CharityDrip Model
 */

class CharityFundraisingEmail extends Pivot
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $table = 'charity_fundraising_email';

    protected $fillable = [
        'charity_id',
        'fundraising_email_id',
        'status',
        'content',
        'from_name',
        'from_email'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    /**
     * Get the charity that subscribed to the fundraising email.
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * Get the fundraising email the charity subscribed to.
     * @return BelongsTo
     */
    public function fundraisingEmail(): BelongsTo
    {
        return $this->belongsTo(FundraisingEmail::class);
    }

    /**
     * The events that belong to the charity fundraising email.
     * @return BelongsToMany
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->using(CharityFundraisingEmailEvent::class)->withPivot('id', 'created_at', 'updated_at');
    }
}
