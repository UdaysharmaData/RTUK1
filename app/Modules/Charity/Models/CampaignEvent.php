<?php

namespace App\Modules\Charity\Models;

use App\Modules\Event\Models\Event;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;

class CampaignEvent extends Pivot
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

    protected $table = 'campaign_event';

    protected $fillable = [
        'campaign_id',
        'event_id',
    ];

    /**
     * Get the campaign
     * @return BelongsTo
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
