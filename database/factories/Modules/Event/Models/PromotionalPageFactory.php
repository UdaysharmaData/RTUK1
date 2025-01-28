<?php

namespace Database\Factories\Modules\Event\Models;

use App\Models\Region;
use Database\Factories\CustomFactory;
use App\Enums\PromotionalPageTypeEnum;
use App\Enums\PromotionalPagePaymentOptionEnum;
use App\Modules\Event\Models\EventPageListing;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\PromotionalPage>
 */
class PromotionalPageFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'event_page_listing_id' => EventPageListing::factory(),
            'title' => $this->faker->firstName(),
            'type' => $this->faker->randomElement(PromotionalPageTypeEnum::cases()),
            'region_id' => Region::factory(),
            'payment_option' => $this->faker->randomElement(PromotionalPagePaymentOptionEnum::cases()),
            'event_page_background_image' => null
        ];
    }
}
