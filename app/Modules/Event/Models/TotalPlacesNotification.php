<?php

namespace App\Modules\Event\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TotalPlacesNotification extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'total_places_notifications';

    protected $fillable = [
        'event_id',
        'charity_id',
        // Todo: Discuss this with the lead. Most events in the database allocates 5 places for classic charities and 20 places for premium charities.
        // Suggestion: Create an attribute which will have these values below as value.
        // 'sent_50',
        // 'sent_25',
        // 'sent_10',
        // 'sent_0',
    ];

}
