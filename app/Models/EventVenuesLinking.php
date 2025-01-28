<?php

namespace App\Models;

use App\Modules\Event\Models\Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventVenuesLinking extends Model
{
    use HasFactory;

    protected $table = 'event_venues_linking';
    protected $fillable = [
        'ref',
        'site_id',
        'event_id',
        'venue_id',
    ];

    // Define relationships
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function venues()
    {
        return $this->belongsTo(Venue::class);
    }
}
