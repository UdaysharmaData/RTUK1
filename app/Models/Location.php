<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Enums\LocationUseAsEnum;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Models\Traits\LocationQueryScopeTrait;

class Location extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute, LocationQueryScopeTrait, HasSpatial;

    protected $table = 'locations';

    protected $fillable = [
        'locationable_id',
        'locationable_type',
        'use_as',
        'address',
        'coordinates',
    ];


    protected $casts = [
        'use_as' => LocationUseAsEnum::class,
        'coordinates' => Point::class,
    ];

    protected $appends = [
        'latitude',
        'longitude'
    ];
    
    /**
     * getLatitudeAttribute
     *
     * @return float
     */
    public function getLatitudeAttribute(): float|null
    {
        return $this->coordinates?->latitude;
    }
    
    /**
     * getLongitudeAttribute
     *
     * @return float
     */
    public function getLongitudeAttribute(): float|null
    {
        return $this->coordinates?->longitude;
    }

    /**
     * @return MorphTo
     */
    public function locationable(): MorphTo
    {
        return $this->morphTo();
    }
}
