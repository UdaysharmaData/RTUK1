<?php

namespace Database\Factories\Modules\Event\Models;

use Str;
use File;
use Database\Traits\SiteTrait;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use Database\Factories\CustomFactory;
use App\Modules\Event\Models\EventCategory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\EventCategory>
 */
class EventCategoryFactory extends CustomFactory
{
    use SiteTrait;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $name = $this->faker->unique()->name();
        $slug = Str::slug($name);

        return [
            'site_id' => static::getSite()?->id,
            'name' => $name,
            'slug' => $slug,
            'color' => $this->faker->hexColor(),
            'distance_in_km' => $this->faker->randomNumber(2)
        ];
    }
}
