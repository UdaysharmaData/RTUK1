<?php

namespace App\Modules\Participant\Models\Relations;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

use App\Modules\User\Models\User;
use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventPage;
use App\Modules\Corporate\Models\Corporate;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Participant\Models\ParticipantAction;
use App\Modules\Participant\Models\FamilyRegistration;
use App\Modules\Participant\Models\ParticipantCustomField;
use App\Modules\Participant\Models\ParticipantExtra;

trait ParticipantRelations
{
    /**
     * Get the user.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the event event category
     *
     * @return BelongsTo
     */
    public function eventEventCategory(): BelongsTo
    {
        return $this->belongsTo(EventEventCategory::class);
    }

    /**
     * Get the charity.
     *
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * Get the corporate.
     *
     * @return BelongsTo
     */
    public function corporate(): BelongsTo
    {
        return $this->belongsTo(Corporate::class);
    }

    /**
     * Get the event page associated with the participant.
     * The event page through which the participant got registered.
     *
     * @return BelongsTo
     */
    public function eventPage(): BelongsTo
    {
        return $this->belongsTo(EventPage::class);
    }

    /**
     * Get the event participant custom field for an event
     *
     * @return HasMany
     */
    public function participantCustomFields(): HasMany
    {
        return $this->hasMany(ParticipantCustomField::class);
    }

    /**
     * Get the family registrations associated with the participant
     *
     * @return HasMany
     */
    public function familyRegistrations(): HasMany
    {
        return $this->hasMany(FamilyRegistration::class);
    }

    /**
     * Get the external enquiry associated with the participant
     *
     * @return HasOne
     */
    public function externalEnquiry(): HasOne
    {
        return $this->hasOne(ExternalEnquiry::class);
    }

    /**
     * Get the website enquiry associated with the participant
     *
     * @return HasOne
     */
    public function enquiry(): HasOne
    {
        return $this->hasOne(Enquiry::class);
    }

    /**
     * Get the actions made on the participant record
     *
     * @return HasMany
     */
    public function participantActions(): HasMany
    {
        return $this->hasMany(ParticipantAction::class);
    }

    /**
     * Get the participant extra associated with the participant
     * 
     * @return HasOne
     */
    public function participantExtra(): HasOne
    {
        return $this->hasOne(ParticipantExtra::class);
    }

    /**
     * Get the event associated with the participant
     *
     * @return HasOneThrough
     */
    public function event(): HasOneThrough
    {
        return $this->hasOneThrough(
            Event::class,
            EventEventCategory::class,
            'id',
            'id',
            'event_event_category_id',
            'event_id'
        )->withDrafted()
        ->withTrashed();
    }
}
