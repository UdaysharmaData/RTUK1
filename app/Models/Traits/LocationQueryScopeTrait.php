<?php

namespace App\Models\Traits;

use App\Enums\Srid;
use UnitConverter\UnitConverter;
use MatanYadaev\EloquentSpatial\Objects\Point;

trait LocationQueryScopeTrait
{


    /**
     * Using the Haversine formula, we can determine the distance between two points on the globe using the latitude and longitude of each point.
     *
     * @param  mixed $query
     * @param  float $latitude
     * @param  float $longitude
     * @param  mixed $radius
     * @return void
     */
    public function scopeWithinRadius($query, float $latitude, float $longitude, ?array $radius = [0, 100])
    {
        if (empty($radius)) {
            $radius = [0, 100];
        }

        $converter = UnitConverter::createBuilder()
            ->addSimpleCalculator()
            ->addDefaultRegistry()
            ->build();

        $lower = $converter->convert($radius[0])->from('mi')->to('m');
        $upper = $converter->convert($radius[1])->from('mi')->to('m');

        $point = new Point($latitude, $longitude, Srid::WGS84->value);

        $query->whereDistance('coordinates', $point, '>=', $lower)
            ->whereDistance('coordinates', $point, '<=', $upper)
            ->orderByDistance('coordinates', $point);
    }
}
