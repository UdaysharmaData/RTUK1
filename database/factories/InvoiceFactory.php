<?php

namespace Database\Factories;

use Str;
use Carbon\Carbon;
use App\Models\Invoice;
use Database\Traits\SiteTrait;
use App\Enums\InvoiceStatusEnum;
use App\Modules\Setting\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Database\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    use SiteTrait;

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (Invoice $invoice) {
            if ($invoice->held) {
                if (! $invoice->send_on) { // This is to avoid overriding the send_on value of the seeder.
                    $invoice->send_on = $this->faker->date('Y-m-d', Carbon::now()->addMonths(2));
                }
            }
        })->afterCreating(function (Invoice $invoice) {
            // $invoice->po_number = $this->generatePoNumber($invoice); 
            $invoice->save();
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $status = $this->faker->randomElement(InvoiceStatusEnum::cases())->value;

        $invoice = $this->faker->randomElement([
            \App\Modules\User\Models\User::class,
            \App\Modules\Charity\Models\Charity::class
        ]);

        return [
            'invoiceable_id' => $invoice::factory(),
            'invoiceable_type' => $invoice,
            'site_id' => static::getSite()?->id,
            'po_number' => null,
            'name' => $this->faker->name(),
            'description' => $this->faker->realText(),
            'charge_id' => $this->faker->randomElement([null, null, Str::random(6)]),
            'refund_id' => $this->faker->randomElement([null, null, Str::random(6)]),
            'discount' => $this->faker->randomElement([$this->faker->randomNumber(1), null, null]),
            'issue_date' => Carbon::now()->addDays(rand(1, 365)),
            'due_date' => Carbon::now()->addDays(rand(1, 365)),
            'price' => $this->faker->randomNumber(6),
            'status' => $status,
            'held' => $this->faker->boolean(),
            'send_on' => null,
        ];
    }

    /**
     * Generate the po_number.
     *
     * @param Invoice $invoice
     * @return string
     */
    private function generatePoNumber(Invoice $invoice)
    {
        $platformCode = $invoice->site->code; // TODO: Figure out a way to identify to which site and invoice belong to
        $date = Carbon::parse($invoice->date)->format('Ymd');
        $type = $this->getTypeInitials($invoice->type);

        return "{$platformCode}{$type}{$date}{$invoice->id}";
    }

    /**
     * The initials of the invoice type.
     * 
     * @param InvoiceItemTypeEnum $type
     * @return string
     */
    private function getTypeInitials(InvoiceItemTypeEnum $type)
    {
        $typeWords = preg_split("/[\s,_-]+/", trim(preg_replace('/([A-Z])/', ' $1', $type->name ?? (InvoiceItemTypeEnum::ParticipantRegistration)->name)));
        $initials = '';

        foreach ($typeWords as $word) {
            $initials .= strtolower($word[0]);
        }

        $type = & $initials;

        return $type;
    }
}
