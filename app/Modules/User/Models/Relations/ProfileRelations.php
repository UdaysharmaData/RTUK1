<?php

namespace App\Modules\User\Models\Relations;

use App\Modules\User\Models\User;
use App\Modules\User\Models\ParticipantProfile;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ProfileRelations
{
    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne
     */
    public function participantProfile(): HasOne
    {
        return $this->hasOne(ParticipantProfile::class);
    }
}
