<?php

namespace App\Modules\Event\Models\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Corporate\Models\Corporate;
use App\Modules\Event\Models\EventEventPage;
use App\Modules\Event\Models\EventPageListing;
use App\Modules\Participant\Models\Participant;
use App\Modules\Event\Models\EventCategoryEventPageListing;

trait EventPageRelations
{
    // /**
    //  * Get the event that owns the event page.
    //  * @return BelongsTo
    //  */
    // public function event(): BelongsTo
    // {
    //     return $this->belongsTo(Event::class);
    // }

    /**
     * Get the events associated with the event page.
     * @return BelongsToMany
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_event_page', 'event_page_id', 'event_id')->using(EventEventPage::class)->withTimestamps();
    }

    /**
     * Get the charity that owns the event page.
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * Get the corporate that owns the event page.
     * @return BelongsTo
     */
    public function corporate(): BelongsTo
    {
        return $this->belongsTo(Corporate::class);
    }

    /**
     * Get the participants that registered for an event through the event page.
     * 
     * @return HasMany
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * Get the event page listings event categories associated with the event page.
     * Relationship between event_category_event_page_listing and event_pages through the event_page_event_category_event_page_listing table.
     * @return BelongsToMany
     */
    public function eventCategoryEventPageListings(): BelongsToMany
    {
        return $this->belongsToMany(EventCategoryEventPageListing::class, 'event_page_event_category_event_page_listing', 'event_page_id', 'event_category_event_page_listing_id')->using(EventPageEventCategoryEventPageListing::class)->withPivot('id', 'created_at', 'updated_at');
    }

    /**
     * Get the event page listings featured event pages associated with the event page.
     * @return BelongsToMany
     */
    public function eventPageListings(): BelongsToMany
    {
        return $this->belongsToMany(EventPageListing::class, 'event_page_event_page_listing', 'event_page_id', 'event_page_listing_id')->using(EventPageEventPageListing::class)->withPivot('id', 'video', 'created_at', 'updated_at');
    }

}