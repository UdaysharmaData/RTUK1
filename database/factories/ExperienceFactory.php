<?php

namespace Database\Factories;

use Str;
use Database\Traits\SiteTrait;
use App\Models\Experience;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Experience>
 */
class ExperienceFactory extends Factory
{
    use SiteTrait;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        Experience::flushEventListeners();

        $experience = $this->experiences[rand(0, count($this->experiences) - 1)];

        return [
            'site_id' => static::getSite()?->id,
            'ref' => Str::orderedUuid(),
            'user_id' => User::factory()->create(['ref' => Str::orderedUuid()]),
            'name' => $experience['name'],
            'icon' => $experience['icon'],
            'values' => $experience['values']
        ];
    }

    public $experiences = [
        [
            'name' => 'Atmosphere',
            'icon' => '🎉',
            'values' => ["UNBELIEVABLE", "AWESOME"]
        ],
        [
            'name' => 'Scenery',
            'icon' => '😚',
            'values' => ["AMAZING"]
        ],
        [
            'name' => 'Elevation',
            'icon' => '💨',
            'values' => ["FANTASTIC", "SERIOUS CLIMBS"]
        ],
        [
            'name' => 'Trail',
            'icon' => '🏞️',
            'values' => ["Trail Run", "Trails", "trail running events"]
        ]
    ];
}
