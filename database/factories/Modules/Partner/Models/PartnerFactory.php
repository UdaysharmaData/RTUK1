<?php

namespace Database\Factories\Modules\Partner\Models;

use Str;
use Carbon\Carbon;
use Database\Traits\SiteTrait;
use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Partner\Models\Partner>
 */
class PartnerFactory extends CustomFactory
{
    use SiteTrait;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $codes = ['ParisHalf', 'RFC1', 'EMF RFC1', '5GDFT884H', 'lets-do-this', null];
        $name = $this->faker->unique()->name();
        $slug = Str::slug($name);

        return [
            'site_id' => static::getSite()?->id,
            'name' => $name,
            'slug' => $slug,
            'description' => $this->faker->text(),
            'website' => $this->faker->url(),
            'code' => $this->faker->randomElement($codes),
        ];
    }
}
