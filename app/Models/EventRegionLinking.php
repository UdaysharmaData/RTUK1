<?php

namespace App\Models;

use App\Modules\Event\Models\Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventRegionLinking extends Model
{
    use HasFactory;

    protected $table = 'event_region_linking';
    protected $fillable = [
        'ref',
        'site_id',
        'event_id',
        'region_id',
    ];

    // Define relationships
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}
