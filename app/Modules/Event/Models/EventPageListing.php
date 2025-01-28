<?php

namespace App\Modules\Event\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Event\Models\EventPageEventPageListing;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Modules\Event\Models\EventCategoryEventPageListing;

use App\Modules\Charity\Models\Charity;
use App\Modules\Corporate\Models\Corporate;

class EventPageListing extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'event_page_listings';

    protected $fillable = [
        'corporate_id',
        'charity_id',
        'title',
        'slug',
        'description',
        'other_events',
        'primary_color',
        'secondary_color',
        'background_image'
    ];

    protected $casts = [
        'other_events' => 'boolean'
    ];

    /**
     * Get the corporate that owns the event page listing.
     * @return BelongsTo
     */
    public function corporate(): BelongsTo
    {
        return $this->belongsTo(Corporate::class);
    }

    /**
     * Get the charity that owns the event page listing.
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * Get the event page listings featured event pages associated with the event page.
     * @return BelongsToMany
     */
    public function eventPages(): BelongsToMany
    {
        return $this->belongsToMany(EventPage::class, 'event_page_event_page_listing', 'event_page_listing_id', 'event_page_id')->using(EventPageEventPageListing::class)->withPivot('id', 'video')->withTimestamps();
    }

    /**
     * Get the event categories of the event page listing.
     * @return BelongsToMany
     */
    public function eventCategories(): BelongsToMany
    {
        return $this->belongsToMany(EventCategory::class, 'event_category_event_page_listing', 'event_page_listing_id', 'event_category_id')->using(EventCategoryEventPageListing::class)->withPivot('id', 'priority')->withTimestamps();
    }

    /**
     * Get the promotional page associated to the event page listing.
     * @return HasOne
     */
    public function promotionalPage(): HasOne
    {
        return $this->hasOne(PromotionalPage::class);
    }
};