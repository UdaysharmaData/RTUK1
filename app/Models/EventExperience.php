<?php

namespace App\Models;

use App\Models\Experience;
use App\Modules\Event\Models\Event;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;

class EventExperience extends Pivot
{
    use HasFactory,
        BelongsToEventTrait;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $table = 'event_experience';

    protected $fillable = [
        'event_id',
        'experience_id',
        'value',
        'description'
    ];

    /**
     * Get the experience
     * 
     * @return BelongsTo
     */
    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }
}
