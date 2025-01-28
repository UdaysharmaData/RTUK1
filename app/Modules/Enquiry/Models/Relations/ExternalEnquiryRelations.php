<?php

namespace App\Modules\Enquiry\Models\Relations;

use App\Modules\User\Models\User;
use App\Modules\Event\Models\Event;
use App\Modules\Setting\Models\Site;
use App\Modules\Charity\Models\Charity;
use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\Partner\Models\PartnerChannel;
use App\Modules\Participant\Models\Participant;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Event\Models\EventCategoryEventThirdParty;
use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;

trait ExternalEnquiryRelations
{
    use BelongsToEventTrait;

    /**
     * Get the site that owns the enquiry.
     * 
     * @return BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the charity that owns the enquiry.
     * 
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * Get the partner Channel that owns the enquiry.
     * 
     * @return BelongsTo
     */
    public function partnerChannel(): BelongsTo
    {
        return $this->belongsTo(PartnerChannel::class);
    }

    /**
     * Get the participant associated with the enquiry.
     * 
     * @return BelongsTo
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * Get the event category (and its equivalence on third party platform) associated with the enquiry.
     * 
     * @return BelongsTo
     */
    public function eventCategoryEventThirdParty(): BelongsTo
    {
        return $this->belongsTo(EventCategoryEventThirdParty::class);
    }

    /**
     * Get the user associated with the enquiry.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    /**
     * Get the website enquiry associated with the external enquiry.
     * 
     * @return HasOne
     */
    public function enquiry(): HasOne
    {
        return $this->hasOne(Enquiry::class);
    }
}