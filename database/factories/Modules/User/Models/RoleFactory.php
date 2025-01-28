<?php

namespace Database\Factories\Modules\User\Models;

use App\Enums\RoleNameEnum;
use Database\Traits\SiteTrait;
use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\User\Models\Role>
 */
class RoleFactory extends CustomFactory
{
    use SiteTrait;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'site_id' => static::getSite()?->id,
            'name' => $this->faker->randomElement(RoleNameEnum::cases()),
            'description' => $this->faker->text()
        ];
    }
}
