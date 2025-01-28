<?php

namespace App\Modules\Event\Models;

use App\Modules\User\Models\User;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Event\Models\EventEventManager;
use App\Enums\EventManagerCompleteNotificationsEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Replaces the manager Model
 */
class EventManager extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'event_managers';

    protected $fillable = [
        'user_id',
        'complete_notifications'
    ];

    protected $casts = [
        'complete_notifications' => EventManagerCompleteNotificationsEnum::class
    ];

    /**
     * Get the user.
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the events managed by the user (event_manager)
     * @return BelongsToMany
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_event_manager', 'event_manager_id', 'event_id')->using(EventEventManager::class)->withPivot('id', 'created_at', 'updated_at');
    }
}
