<?php

namespace Database\Seeders;

use DB;
use Schema;
use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Seeder;
use Database\Traits\FormatDate;
use App\Enums\InvoiceStatusEnum;
use App\Http\Helpers\RegexHelper;
use App\Enums\InvoiceItemTypeEnum;
use Illuminate\Support\Facades\Log;
use App\Modules\Charity\Models\Charity;
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\Charity\Models\EventPlaceInvoice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EventPlaceInvoiceSeeder extends Seeder
{
    use FormatDate, EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The event place invoice seeder logs');

        $this->truncateTable();

        $epis = DB::connection('mysql_2')->table('event_place_invoices')->get();

        foreach ($epis as $epi) {
            $invoice = Invoice::find($epi->invoice_id);
            $charity = Charity::find($epi->charity_id);
            $charity = $charity ?? Charity::factory()->create(['id' => $epi->charity_id]);

            $epi = EventPlaceInvoice::factory()
                ->for($charity)
                ->create([
                    'id' => $epi->id,
                    'year' => $epi->year,
                    'period' => $epi->period,
                    'status' => $epi->status,
                    'invoice_sent_on' => $epi->invoice_sent_on
                ]);

                if ($this->valueOrDefault($epi->invoice_id)) {
                    if (Invoice::where('type', InvoiceItemTypeEnum::EventPlaces)->where('id', $epi->invoice_id)->doesntExist()) {
                        $invoice = DB::connection('mysql_2')->table('invoices')->where('id', $epi->invoice_id)->first();

                        $_invoice = $epi->invoice()->create([
                            'id' => $epi->invoice_id,
                            'invoiceable_type' => Charity::class,
                            'invoiceable_id' => $charity->id,
                            'site_id' => null, // TODO: Look into this and figure out a way to identify from which site an invoice was made.
                            'po_number' => $invoice?->po_number,
                            'name' => 'Invoice for '. RegexHelper::format(InvoiceItemTypeEnum::EventPlaces->name) . ' on behalf of '. $charity->name,
                            'description' => "Please find below an invoice for the order you requested.",
                            'charge_id' => $this->valueOrDefault($invoice?->charge_id),
                            'refund_id' => $this->valueOrDefault($invoice?->refund_id),
                            'issue_date' => $this->dateOrNow($invoice?->date),
                            'due_date' => Carbon::parse($this->dateOrNow($invoice?->date))->addWeeks(2)->toDateString(),
                            'price' => $invoice?->price,
                            'status' => $this->valueOrDefault($invoice?->status, InvoiceStatusEnum::Paid),
                            'held' => $invoice?->held,
                            'send_on' => Carbon::parse($invoice?->send_on)->toDateString(),
                            'created_at' => $invoice?->created_at,
                            'updated_at' => $invoice?->updated_at
                        ]);

                        InvoiceItem::factory()
                            ->for($_invoice)
                            ->create([
                                'invoice_itemable_type' => EventPlaceInvoice::class,
                                'invoice_itemable_id' => $epi->id,
                                'type' => $this->valueOrDefault($invoice?->category, InvoiceItemTypeEnum::ParticipantRegistration),
                                'discount' => null,
                                'price' => $_invoice->price
                            ]);

                        Log::channel('dataimport')->debug("id: {$epi->id} The invoice id {$epi->invoice_id} did not exists and was created. Invoice: ".json_encode($_invoice));
                    }
                } else {
                    Log::channel('dataimport')->debug("id: {$epi->id} The event place does not have an invoice_id. Event_place_invoice: ".json_encode($epi));
                }

            if (!$charity) {
                Log::channel('dataimport')->debug("id: {$epi->id} The charity id {$epi->charity_id} did not exists and was created. Event_place_invoice: ".json_encode($epi));
            }

            if (!$invoice) {
                Log::channel('dataimport')->debug("id: {$epi->id} The invoice id {$epi->invoice_id} did not exists and was created. Event_place_invoice: ".json_encode($epi));
            }
        }
    }

    /**
     * Truncate the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        EventPlaceInvoice::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
