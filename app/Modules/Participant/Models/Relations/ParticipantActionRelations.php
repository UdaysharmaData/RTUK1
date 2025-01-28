<?php

namespace App\Modules\Participant\Models\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Modules\User\Models\User;
use App\Modules\User\Models\Role;
use App\Modules\Participant\Models\Participant;

trait ParticipantActionRelations
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
     * Get the user making the action.
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the role of the user while making the action.
     * 
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}