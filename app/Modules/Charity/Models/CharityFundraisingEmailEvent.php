<?php

namespace App\Modules\Charity\Models;

use App\Modules\Event\Models\Event;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;

class CharityFundraisingEmailEvent extends Pivot
{
    use HasFactory,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        BelongsToEventTrait;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $table = 'charity_fundraising_email_event';

    protected $fillable = [
        'charity_fundraising_email_id',
        'event_id'
    ];

    /**
     * Get the charity fundraising email
     * @return BelongsTo
     */
    public function charityFundraisingEmail(): BelongsTo
    {
        return $this->belongsTo(CharityFundraisingEmail::class);
    }
}
