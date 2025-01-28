<?php

namespace Database\Factories\Modules\Charity\Models;

use Carbon\Carbon;
use Database\Factories\CustomFactory;
use App\Modules\Partner\Models\Partner;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\PartnerPackage>
 */
class PartnerPackageFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $names = ['Run Through Drop Downs', 'Run Through Title Partner', 'Virtual Medal Order - 300 Medals'];
        $startDate = $this->faker->date();
        $endDate = Carbon::parse($startDate)->addMonths(10);
        $renewalDate = Carbon::parse($endDate)->addDay();
        $renewedAt = Carbon::parse($renewalDate)->addDay();

        return [
            'partner_id' => Partner::factory(),
            'name' => $this->faker->randomElement($names),
            'price' => $this->faker->randomNumber(4, true),
            'quantity' => $this->faker->randomNumber(3),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'renewal_date' => $renewalDate,
            'description' => $this->faker->sentence(),
            'price_commission' => $this->faker->randomElement([$this->faker->randomNumber(3), null]),
            'renewal_commission' => $this->faker->randomElement([$this->faker->randomNumber(3), null]),
            'new_business_commission' => $this->faker->randomElement([$this->faker->randomNumber(3), null]),
            'partner_split_after_commission' => $this->faker->randomElement([$this->faker->randomNumber(3), null]),
            'rfc_split_after_commission' => $this->faker->randomElement([$this->faker->randomNumber(3), null]),
            'renewed_at' => $renewedAt
        ];
    }
}
