<?php

namespace App\Traits;

use App\Models\City;
use App\Models\Region;
use App\Models\Venue;
use App\Modules\Event\Models\EventCategory;
use Illuminate\Support\Str;

trait AddCombinationPathParameter
{
    /**
     * @param $key
     * @param $default
     * @return array
     */
    public function validated($key = null, $default = null): array
    {
        if ($this->missing('path')) {
            return array_merge(parent::validated(), ['path' => $this->generatePath()]);
        }

        return parent::validated();
    }


    /**
     * @return string
     */
    public function generatePath(): string
    {
        $relations = [
            $this->region_id ? Region::find($this->region_id)?->slug : null,
            $this->city_id ? City::find($this->city_id)?->slug : null,
            $this->venue_id ? Venue::find($this->venue_id)->slug : null,
            $this->event_category_id ? EventCategory::find($this->event_category_id)?->slug : null
        ];

        $path = '';

        foreach ($relations as $key => $value) {
            if (! is_null($value)) $path .= "/$value";
        }

        return $path;
    }
}
