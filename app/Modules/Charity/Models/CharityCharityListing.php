<?php

namespace App\Modules\Charity\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Event\Models\EventPage;
use App\Enums\CharityCharityListingTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Replaces CharityPartnerListing model
 */
class CharityCharityListing extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'charity_charity_listing';

    protected $fillable = [
        'charity_listing_id',
        'charity_id',
        'event_page_id',
        'type',
        'url'
    ];

    protected $casts = [
        'type' => CharityCharityListingTypeEnum::class
    ];

    /**
     * Get the charity that belongs to the listing
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * Get the event page that belongs to the listing
     * @return BelongsTo
     */
    public function eventPage(): BelongsTo
    {
        return $this->belongsTo(EventPage::class);
    }

    /**
     * Get the charity listing to which the charity belongs
     * @return BelongsTo
     */
    public function charityListing(): BelongsTo
    {
        return $this->belongsTo(CharityListing::class);
    }
}
