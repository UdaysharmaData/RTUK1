<?php

namespace App\Modules\Event\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\Uploadable\HasOneUpload;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\Uploadables\CanHaveUploadableResource;

class EventPageEventPageListing extends Pivot implements CanHaveUploadableResource
{
    use HasFactory, HasOneUpload, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $table = 'event_page_event_page_listing';

    protected $fillable = [
        'event_page_listing_id',
        'event_page_id',
        'video'
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
     * Get the event page.
     * @return BelongsTo
     */
    public function eventPage(): BelongsTo
    {
        return $this->belongsTo(EventPage::class);
    }
}
