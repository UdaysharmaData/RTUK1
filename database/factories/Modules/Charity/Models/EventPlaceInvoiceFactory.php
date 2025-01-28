<?php

namespace Database\Factories\Modules\Charity\Models;

use Carbon\Carbon;
use App\Models\Invoice;
use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;
use App\Enums\EventPlaceInvoicePeriodEnum;
use App\Enums\EventPlaceInvoiceStatusEnum;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\EventPlaceInvoice>
 */
class EventPlaceInvoiceFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'charity_id' => Charity::factory(),
            'year' => $this->faker->year(Carbon::now()->addYears(5)->year),
            'period' => $this->faker->randomElement(['03_05', '06_08', '09_11', '12_02']),
            // 'period' => $this->faker->randomElement(EventPlaceInvoicePeriodEnum::cases()),
            'invoice_sent_on' => $this->faker->dateTime()
        ];
    }
}
