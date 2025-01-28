<?php

namespace App\Modules\Event\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;

class EventCategoryPromotionalEvent extends Pivot
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

    protected $table = 'event_category_promotional_event';

    protected $fillable = [
        'event_category_id',
        'event_id'
    ];

    /**
     * Get the event category.
     * @return BelongsTo
     */
    public function eventCategory(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
    }
}
