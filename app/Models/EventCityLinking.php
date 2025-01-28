<?php

namespace App\Models;

use App\Modules\Event\Models\Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventCityLinking extends Model
{
    use HasFactory;

    protected $table = 'event_city_linking';
    protected $fillable = [
        'ref',
        'site_id',
        'event_id',
        'city_id',
    ];

    // Define relationships
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function cities()
    {
        return $this->belongsTo(City::class);
    }
}
