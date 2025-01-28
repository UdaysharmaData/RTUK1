<?php

namespace App\Modules\Participant\Models\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Modules\Participant\Models\Participant;

trait ParticipantExtraRelations
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
}