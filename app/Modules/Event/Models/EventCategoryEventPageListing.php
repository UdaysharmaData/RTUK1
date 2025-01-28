<?php

namespace App\Modules\Event\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Modules\Event\Models\EventPageEventCategoryEventPageListing;

class EventCategoryEventPageListing extends Pivot
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $table = 'event_category_event_page_listing';

    protected $fillable = [
        'event_page_listing_id',
        'event_category_id',
        'priority'
    ];

    /**
     * Get the event page listing.
     * @return BelongsTo
     */
    public function eventPageListing(): BelongsTo
    {
        return $this->belongsTo(EventPageListing::class);
    }

    /**
     * Get the event category.
     * @return BelongsTo
     */
    public function eventCategory(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
    }

    /**
     * Get the event pages.
     * Relationship between event_category_event_page_listing and event_pages through the event_page_event_category_event_page_listing table.
     * @return BelongsToMany
     */
    public function eventPages(): BelongsToMany
    {
        return $this->belongsToMany(EventPage::class, 'event_page_event_category_event_page_listing', 'event_category_event_page_listing_id', 'event_page_id')->using(EventPageEventCategoryEventPageListing::class)->withPivot('id', 'created_at', 'updated_at');
    }
}
