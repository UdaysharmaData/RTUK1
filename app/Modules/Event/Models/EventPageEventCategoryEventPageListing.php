<?php

namespace App\Modules\Event\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventPageEventCategoryEventPageListing extends Pivot
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'event_page_event_category_event_page_listing';

    protected $fillable = [
        'event_category_event_page_listing_id',
        'event_page_id'
    ];

    /**
     * Get the event category of the event page listing.
     */
    public function eventCategoryEventPageListing()
    {
        return $this->belongsTo(EventCategoryEventPageListing::class);
    }

    /**
     * Get the event page.
     * @return BelongsTo
     */
    public function eventPage(): BelongsTo
    {
        return $this->belongsTo(EventPage::class);
    }
}
