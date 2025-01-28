<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Enums\InvoiceItemTypeEnum;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Database\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        $types = [
            \App\Modules\Participant\Models\Participant::class => InvoiceItemTypeEnum::ParticipantRegistration,
            \App\Modules\Charity\Models\ResaleRequest::class => InvoiceItemTypeEnum::MarketResale,
            \App\Modules\Charity\Models\CharityMembership::class => InvoiceItemTypeEnum::CharityMembership,
            \App\Modules\Charity\Models\EventPlaceInvoice::class => InvoiceItemTypeEnum::EventPlaces,
            \App\Modules\Charity\Models\CharityPartnerPackage::class => InvoiceItemTypeEnum::PartnerPackageAssignment
        ];

        return $this->afterMaking(function (InvoiceItem $item) use ($types) {
            $item->type = $types[$item->invoice_itemable_type]->value;
            $item->save();
        })->afterCreating(function (InvoiceItem $item) {
            // $item->save();
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $type = $this->faker->randomElement(InvoiceItemTypeEnum::cases())->value;

        $invoiceItem = $this->faker->randomElement([
            \App\Modules\Participant\Models\Participant::class,
            \App\Modules\Charity\Models\ResaleRequest::class,
            \App\Modules\Charity\Models\CharityMembership::class,
            \App\Modules\Charity\Models\EventPlaceInvoice::class,
            \App\Modules\Charity\Models\CharityPartnerPackage::class
        ]);

        return [
            'invoice_itemable_id' => $invoiceItem::factory(),
            'invoice_itemable_type' => $invoiceItem,
            'invoice_id' => Invoice::factory(),
            'type' => $type,
            'discount' => $this->faker->randomElement([$this->faker->randomNumber(1), null, null]),
            'price' => $this->faker->randomNumber(6)
        ];
    }
}
