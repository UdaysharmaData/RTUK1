<?php

namespace App\Http\Helpers;

use App\Contracts\Locationables\CanHaveManyLocationableResource;
use App\Enums\LocationUseAsEnum;
use App\Enums\Srid;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use MatanYadaev\EloquentSpatial\Objects\Point;

class LocationHelper
{
    /**
     * Geocode the address to get the latitude and longitude
     * 
     * @return ?Array
     */
    public static function geocodeAddress(string $address): ?array
    {
        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $address,
            'key' => config('app.google_api_key'),
        ]);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Get the latitude and longitude
     * 
     * @return ?Array
     */
    public function getLatLng(string $address): ?array
    {
        $location = Cache::get($this->getCacheKey($address));

        if ($location) {
            return $location;

        } else {
            $response = static::geocodeAddress($address);

            if ($response && $response['status'] === 'OK' && isset($response['results'][0]) && isset($response['results'][0]['geometry']) && isset($response['results'][0]['geometry']['location'])) {
                $location = $response['results'][0]['geometry']['location'];
                $this->cacheLatLng($address, $location);
                return $location;
            }

            return null;
        }
    }

        
    /**
     * Cache the latitude and longitude
     *
     * @param  mixed $address
     * @param  mixed $latLng
     * @return void
     */
    private function cacheLatLng(string $address, array $latLng): void
    {
        Cache::put($this->getCacheKey($address), $latLng);
    }

    /**
     * Get the cache key for the latitude and longitude
     *
     * @param  mixed $address
     * @return string
     */
    private function getCacheKey(string $address): string
    {
        return sha1('google-geo-location.' . $address);
    }

    /**
     * Save the address
     *
     * @param  CanHaveManyLocationableResource $model
     * @param  mixed $request
     * @return void
     */
    public static function saveAddress(CanHaveManyLocationableResource $model, $request)
    {
        $location = $request->location;

        $model->address()->updateOrCreate([], [
            'address' => $location['address'],
            'use_as' => LocationUseAsEnum::Address,
            'coordinates' => new Point($location['latitude'], $location['longitude'], Srid::WGS84->value)
        ]);

        return $model;
    }
}
