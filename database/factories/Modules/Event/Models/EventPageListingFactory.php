<?php

namespace Database\Factories\Modules\Event\Models;

use Str;
use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;
use App\Modules\Corporate\Models\Corporate;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\EventPageListing>
 */
class EventPageListingFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $title = $this->faker->unique->name();
        $slug = Str::slug($title);

        return [
            'charity_id' => Charity::factory(),
            'corporate_id' => $this->faker->randomElement([null, Corporate::factory()]),
            'title' => $title,
            'slug' => $slug,
            'description' => $this->faker->text(),
            'other_events' => $this->faker->boolean(),
            'primary_color' => $this->faker->hexColor(),
            'secondary_color' => $this->faker->hexColor(),
            'background_image' => null
        ];
    }
}
