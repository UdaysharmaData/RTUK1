<?php

namespace App\Modules\Event\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;

/**
 * The events managed by event_managers.
 * Replaces EventManager model
 */
class EventEventManager extends Pivot
{
    use BelongsToEventTrait;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'event_event_manager';

    protected $fillable = [
        'event_id',
        'event_manager_id',
    ];

    /**
     * Get the event manager.
     */
    public function eventManager(): BelongsTo
    {
        return $this->belongsTo(EventManager::class);
    }
}
