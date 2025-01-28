<?php

namespace App\Modules\Enquiry\Models\Relations;

use App\Modules\User\Models\User;
use App\Modules\Event\Models\Event;
use App\Modules\Setting\Models\Site;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Corporate\Models\Corporate;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Modules\Participant\Models\Participant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;

trait EnquiryRelations
{
    use BelongsToEventTrait;

    /**
     * Get the charity that owns the enquiry.
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * Get the event category associated with the enquiry.
     * 
     * @return BelongsTo
     */
    public function eventCategory(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
    }

    /**
     * Get the corporate that owns the enquiry.
     * 
     * @return BelongsTo
     */
    public function corporate(): BelongsTo
    {
        return $this->belongsTo(Corporate::class);
    }

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
     * Get the participant associated with the enquiry.
     * 
     * @return BelongsTo
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
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
     * Get the external enquiry associated with the enquiry.
     * 
     * @return BelongsTo
     */
    public function externalEnquiry(): BelongsTo
    {
        return $this->belongsTo(ExternalEnquiry::class);
    }
}