<?php

namespace Database\Factories\Modules\User\Models;

use App\Modules\User\Models\User;
use App\Modules\Setting\Models\Site;
use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\User\Models\SiteUser>
 */
class SiteUserFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'site_id' => Site::factory()
        ];
    }
}
