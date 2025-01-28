<?php

namespace App\Modules\Event\Models;

use App\Enums\ListingPageTypeEnum;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\CharityListing;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;

/**
 * Replaces ListingsPage.
 * Used to create a charity listing with or without events.
 * When the event is set, the event pages of the charity listing primary, secondary and 2_year(if set) partners are created for the event.
 * NOTE: The code doesn't check whether the event page exists before creating it. Discuss about this and on how to improve on this with the Team Lead during implementation.
 */
class ListingPage extends Model
{
    use HasFactory,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        BelongsToEventTrait;

    protected $table = 'listing_pages';

    protected $fillable = [
        // 'charity_id',
        'event_id',
        'charity_listing_id',
        'title',
        'type',
        'event_page_description'
    ];

    protected $casts = [
        'type' => ListingPageTypeEnum::class
    ];

    /**
     * Get the charity.
     * @return BelongsTo
     */
    // public function charity(): BelongsTo
    // {
    //     return $this->belongsTo(Charity::class);
    // }

    /**
     * Get the charity listing associated with the listing page.
     * @return BelongsTo
     */
    public function charityListing()
    {
        return $this->belongsTo(CharityListing::class);
    }
}
