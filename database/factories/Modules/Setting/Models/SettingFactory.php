<?php

namespace Database\Factories\Modules\Setting\Models;

use App\Modules\Setting\Models\Site;
use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Setting\Models\Setting>
 */
class SettingFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'site_id' => Site::factory()
        ];
    }
}
