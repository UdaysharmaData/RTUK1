<?php

namespace Database\Factories\Modules\User\Models;

use App\Modules\Setting\Enums\SiteEnum;
use Database\Traits\SiteTrait;
use App\Modules\Setting\Models\Site;
use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\User\Models\Permission>
 */
class PermissionFactory extends CustomFactory
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
            'site_id' => static::getSite()?->id ?? Site::where('domain', SiteEnum::RunThrough->value)->first()?->id,
            'name' => $this->faker->unique()->name(),
            'description' => $this->faker->sentence()
        ];
    }
}
