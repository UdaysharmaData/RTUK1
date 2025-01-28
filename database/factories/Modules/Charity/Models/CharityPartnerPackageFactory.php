<?php

namespace Database\Factories\Modules\Charity\Models;

use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;
// use App\Modules\User\Models\Contract;
use App\Modules\Charity\Models\PartnerPackage;
use App\Enums\CharityPartnerPackageStatusEnum;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\CharityPartnerPackage>
 */
class CharityPartnerPackageFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'partner_package_id' => PartnerPackage::factory(),
            'charity_id' => Charity::factory(),
            // 'contract_id' => $this->faker->randomElement([Contract::factory(), null]),
            'status' => $this->faker->randomElement(CharityPartnerPackageStatusEnum::cases())
        ];
    }
}
