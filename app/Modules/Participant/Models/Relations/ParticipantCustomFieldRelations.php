<?php

namespace App\Modules\Participant\Models\Relations;


use App\Modules\Event\Models\EventCustomField;
use App\Modules\Participant\Models\Participant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ParticipantCustomFieldRelations
{
    /**
     * Get the participant.
     * 
     * @return BelongsTo
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * Get the event custom field
     * 
     * @return BelongsTo
     */
    public function eventCustomField(): BelongsTo
    {
        return $this->belongsTo(EventCustomField::class);
    }
}