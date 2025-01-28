<?php

namespace App\Modules\User\Models\Relations;

use App\Modules\User\Models\Profile;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ParticipantProfileRelations
{
    /**
     * Get the profile.
     * 
     * @return BelongsTo
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}