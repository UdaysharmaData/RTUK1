<?php

namespace Database\Factories\Modules\Setting\Models;

use App\Modules\Setting\Models\Site;
use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Setting\Models\Site>
 */
class SiteFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'key' => Site::generateRandomString(),
            'domain' => $this->faker->domainName(),
            'name' => $this->faker->name(),
            'code' => null,
            'status' => $this->faker->boolean(95),
        ];
    }
}
