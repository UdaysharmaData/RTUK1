<?php

namespace App\Modules\Event\Models;

use App\Models\Region;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;

class PromotionalFeaturedEvent extends Model
{
    use HasFactory,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        BelongsToEventTrait;

    protected $table = 'promotional_featured_events';

    protected $fillable = [
        'region_id',
        'event_id',
    ];

    /**
     * Get the region.
     * 
     * @return BelongsTo
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
