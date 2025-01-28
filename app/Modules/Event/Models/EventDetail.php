<?php

namespace App\Modules\Event\Models;

use App\Traits\AddUuidRefAttribute;
use App\Modules\Event\Models\Event;
use App\Modules\Setting\Models\Site;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;

class EventDetail extends Model
{
    use HasFactory,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        BelongsToEventTrait;

    protected $table = 'event_details';

    protected $fillable = [
        'site_id',
        'event_id',
        // 'description'
    ];

    /**
     * Get the site that owns the event.
     * @return BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
