<?php

namespace Database\Factories\Modules\Partner\Models;

use Str;
use Database\Factories\CustomFactory;
use App\Modules\Partner\Models\Partner;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Partner\Models\PartnerChannel>
 */
class PartnerChannelFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $name = $this->faker->unique()->name();
        $code = Str::slug($name);

        return [
            'partner_id' => Partner::factory(),
            'name' => $name,
            'code' => $code
        ];
    }
}
