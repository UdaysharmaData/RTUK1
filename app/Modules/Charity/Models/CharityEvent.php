<?php

namespace App\Modules\Charity\Models;

use App\Enums\CharityEventTypeEnum;
use App\Modules\Event\Models\Event;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;

/**
 * Replaces CharityPlace model
 */
class CharityEvent extends Pivot
{
    use HasFactory,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        BelongsToEventTrait;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $table = 'charity_event';

    protected $fillable = [
        'event_id',
        'charity_id',
        'type'
    ];

    protected $casts = [
        'type' => CharityEventTypeEnum::class
    ];

    /**
     * Get the charity
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }
}
