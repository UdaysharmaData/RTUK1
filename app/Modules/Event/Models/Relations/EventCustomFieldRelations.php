<?php

namespace App\Modules\Event\Models\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Modules\Event\Models\Event;
use App\Modules\Participant\Models\FamilyRegistration;

use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;

trait EventCustomFieldRelations
{
    use BelongsToEventTrait;

    /**
     * Get the family registrations associated with the event custom field
     * 
     * @return HasMany
     */
    public function familyRegistrations(): HasMany
    {
        return $this->hasMany(FamilyRegistration::class);
    }

}