<?php

namespace App\Modules\Event\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;

class EventEventPage extends Pivot
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


    protected $table = 'event_event_page';

    protected $fillable = [
        'event_page_id',
        'event_id',
    ];

    /**
     * Get the event page.
     * @return BelongsTo
     */
    public function eventPage(): BelongsTo
    {
        return $this->belongsTo(EventPage::class);
    }
}
