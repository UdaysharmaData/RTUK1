<?php

namespace Database\Factories;

use Str;
use File;
use App\Models\Region;
use Database\Traits\SiteTrait;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Database\Factories\Factory<\App\Models\Region>
 */
class RegionFactory extends Factory
{
    use SiteTrait;

    /**
     * Create the region's image
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (Region $region) { // Ensure the region does not exists for the site making the request
            $_region = Region::where('site_id', $region->site_id)
                ->where('name', $region->name);

            if ($_region->exists()) {
                $region->name .= Str::random(10);
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'site_id' => static::getSite()?->id,
            'name' => $this->faker->unique()->name(),
            'description' => $this->faker->text(),
        ];
    }
}
