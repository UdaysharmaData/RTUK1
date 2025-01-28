<?php

namespace Database\Seeders;

use DB;
use Str;
use Schema;
use Storage;
use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use Database\Traits\FormatDate;
use Illuminate\Database\Seeder;
use App\Enums\InvoiceStatusEnum;
use App\Modules\User\Models\User;
use App\Http\Helpers\RegexHelper;
use App\Enums\InvoiceItemTypeEnum;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\Event;
use App\Enums\ParticipantStatusEnum;
use App\Enums\ResaleRequestStateEnum;
use App\Modules\Charity\Models\Charity;
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\Charity\Models\ResaleRequest;
use App\Modules\Participant\Models\Participant;
use App\Modules\Charity\Models\EventPlaceInvoice;
use App\Modules\Charity\Models\CharityPartnerPackage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class InvoiceSeeder extends Seeder
{
    use FormatDate, EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The invoice seeder logs');

        $this->truncateTable();

        $invoices = DB::connection('mysql_2')->table('invoices')->get();

        InvoiceItem::flushEventListeners(); // Avoid the pdf file from being deleted and recreated once the invoice item gets created.

        foreach ($invoices as $invoice) { // TODO: Revise this logic and ensure every invoiceItem gets associated with the model corresponding to it's type
            $foreignKeyColumns = [];
            $invoiceItemForeignKeyColumns = [];

            $_invoice = Invoice::factory();

            if ($this->valueOrDefault($invoice->user_id) && $invoice->category == InvoiceItemTypeEnum::ParticipantRegistration->value) { // If the invoice is for participant, set the user_id to the invoiceable_id here
                $user = User::find($invoice->user_id);
                $_user = $user ?? User::factory()->unverified()->create(['id' => $invoice->user_id]);

                $foreignKeyColumns = ['invoiceable_type' => User::class, 'invoiceable_id' => $_user->id];

                // Check if the participant linked to this invoice exists on the sport-for-api database
                $participant = DB::connection('mysql_2')->table('participants')->where('user_id', $_user->id)
                    ->where(function ($query) use ($invoice) {
                        $query->where('invoice_id', $invoice->id)
                            ->orWhere('charge_id', $invoice->charge_id);
                            // ->orWhere('refund_id', $invoice->refund_id)
                    })->first();

                if ($participant) {
                    $_participant = Participant::where('user_id', $_user->id) // Get the participant having the invoice
                        ->where('id', $participant->id)
                        ->withTrashed()
                        ->first();

                    if ($_participant) {
                        $invoiceItemForeignKeyColumns = ['invoice_itemable_type' => Participant::class, 'invoice_itemable_id' => $_participant->id];
                    } else {
                        Log::channel('dataimport')->debug("id: {$invoice->id} The user id {$_user->id} having the participant_id {$participant->id} could not be linked to a participant. Invoice: ".json_encode($invoice));
                    }
                } else { // Create the participant
                    $_participant = Participant::factory()
                        ->create([
                            'user_id' => $_user->id,
                            // 'event_id' => // TODO: Figure out a way to set the event_id. No way was found for now. Therefore the seeder will create an event for it. Update this to ensure the event's local_registration_fee (local_fee + env('PARTICIPANT_REGISTRATION_CHARGE_RATE')) matches the invoice price
                            'status' => ParticipantStatusEnum::Notified
                        ]);

                    $invoiceItemForeignKeyColumns = ['invoice_itemable_type' => Participant::class, 'invoice_itemable_id' => $_participant->id];

                    Log::channel('dataimport')->debug("id: {$invoice->id} The participant with the user_id {$invoice->user_id}, invoice_id {$invoice->id}, charge_id {$invoice->charge_id} did not exists and was created. Invoice: ".json_encode($invoice));
                }
            } else { // The invoice is for the charity if it is not for the participant
                $charity = Charity::withTrashed()
                    ->find($invoice->charity_id);
                $_charity = $charity ?? Charity::factory()->create(['id' => $invoice->charity_id]);

                $foreignKeyColumns = ['invoiceable_type' => Charity::class, 'invoiceable_id' => $_charity->id];
            }

            $_invoice = $_invoice->create([
                ...$foreignKeyColumns,
                'id' => $invoice->id,
                'site_id' => null, // TODO: Look into this and figure out a way to identify from which site an invoice was made.
                'po_number' => $invoice->po_number,
                'name' => $this->getName($invoice, isset($_charity) ? $_charity : null, isset($_participant) ? $_participant : null),
                'description' => "Please find below an invoice for the order you requested.",
                'charge_id' => $this->valueOrDefault($invoice->charge_id),
                'refund_id' => $this->valueOrDefault($invoice->refund_id),
                'issue_date' => $this->dateOrNow($invoice->date),
                'due_date' => Carbon::parse($this->dateOrNow($invoice->date))->addWeeks(2)->toDateString(),
                'price' => $invoice->price,
                'discount' => null,
                'status' => $this->valueOrDefault($invoice->status, InvoiceStatusEnum::Paid),
                'held' => $invoice->held,
                'send_on' => Carbon::parse($this->dateOrNull($invoice->send_on) ? $invoice->send_on : $invoice->date)->toDateString(),
                'created_at' => $invoice->created_at,
                'updated_at' => $invoice->updated_at
            ]);

            if ($this->valueOrDefault($invoice->pdf)) { // save the pdf path
                $file = $_invoice->upload()->updateOrCreate([], [
                    'title' => $_invoice->name,
                    'type' => UploadTypeEnum::PDF,
                    'use_as' => UploadUseAsEnum::Image,
                    'url' => config('app.pdfs_path') . str_replace('/uploads/pdf/', '', $invoice->pdf)
                ]);

                if (Storage::disk('sfc')->exists($invoice->pdf)) { // Copy the pdf
                    Storage::disk('local')->put('public'.$file->url, Storage::disk('sfc')->get($invoice->pdf));
                }
            }

            // Create the invoice items (The invoices on the sport-for-api database will have just one item each)
            $_invoiceItem = InvoiceItem::factory();

            if ($this->valueOrDefault($invoice->assigned_partner_package_id) && $invoice->category == InvoiceItemTypeEnum::PartnerPackageAssignment->value) { // check if the invoice is for a charity partner package
                $cpp = CharityPartnerPackage::find($invoice->assigned_partner_package_id);
                $_cpp = $cpp ?? CharityPartnerPackage::factory()->create(['id' => $invoice->assigned_partner_package_id]);

                $invoiceItemForeignKeyColumns = ['invoice_itemable_type' => CharityPartnerPackage::class, 'invoice_itemable_id' => $_cpp->id];

                if (!$cpp) {
                    Log::channel('dataimport')->debug("id: {$invoice->id} The assigned partner package id {$invoice->assigned_partner_package_id} did not exists and was created. Invoice: ".json_encode($invoice));
                }
            }

            else if ($this->valueOrDefault($invoice->charity_id) && $invoice->category == InvoiceItemTypeEnum::EventPlaces->value) { // check if the invoice is for an event place invoice
                $epi = DB::connection('mysql_2')->table('event_place_invoices')->where('invoice_id', $invoice->id)->where('charity_id', $invoice->charity_id)->first();

                if ($epi) { // Ensure the event place invoice exists on the sport-for-api database
                    $epi = EventPlaceInvoice::where('id', $epi->id)
                        ->where('charity_id', $invoice->charity_id)
                        ->first();

                    $_epi = $epi ?? EventPlaceInvoice::factory()->create(['charity_id' => $invoice->charity_id]);

                    $invoiceItemForeignKeyColumns = ['invoice_itemable_type' => EventPlaceInvoice::class, 'invoice_itemable_id' => $_epi->id];

                    if (!$epi) {
                        Log::channel('dataimport')->debug("id: {$invoice->id} The event place invoice {$invoice->charity_id} did not exists and was created. Invoice: ".json_encode($invoice));
                    }
                } else {
                    Log::channel('dataimport')->debug("id: {$invoice->id} The invoice {$invoice->id} is not linked to an event place invoice on the sport-for-api database. Invoice: ".json_encode($invoice));
                }
            }

            else if ($this->valueOrDefault($invoice->charity_id) && $invoice->charge_id && $invoice->category == InvoiceItemTypeEnum::MarketResale->value) { // check if the invoice is for a market resale
                $resaleRequest = ResaleRequest::where('charity_id', $invoice->charity_id)
                    ->where('charge_id', $invoice->charge_id)
                    ->where('state', ResaleRequestStateEnum::Paid)
                    ->first();

                $_resaleRequest = $resaleRequest ?? ResaleRequest::factory()->create(['charity_id' => $invoice->charity_id]);

                $invoiceItemForeignKeyColumns = ['invoice_itemable_type' => ResaleRequest::class, 'invoice_itemable_id' => $_resaleRequest->id];

                if (!$resaleRequest) {
                    Log::channel('dataimport')->debug("id: {$invoice->id} The resale request was not found and got created. Invoice: ".json_encode($invoice));
                }
            }

            else if ($this->valueOrDefault($invoice->charity_id) && in_array($invoice->category, [InvoiceItemTypeEnum::CharityMembership->value, InvoiceItemTypeEnum::ParticipantRegistration->value])) { // Check if the invoice is for any of these types. participant_registration is actually not supposed to be among but since there is one record of it on the previous database, it was added.

                $invoiceItemForeignKeyColumns = ['invoice_itemable_type' => Charity::class, 'invoice_itemable_id' => $_charity->id];

                if (!$charity) {
                    Log::channel('dataimport')->debug("id: {$invoice->id} The charity id {$invoice->charity_id} did not exists and was created. Invoice: ".json_encode($invoice));
                }
            }

            else if ($this->valueOrDefault($invoice->user_id) && $invoice->category == InvoiceItemTypeEnum::ParticipantRegistration->value) { // check if the invoice is for a participant
                if (!$user) {
                    Log::channel('dataimport')->debug("id: {$invoice->id} The user id {$invoice->user_id} did not exists and was created. Invoice: ".json_encode($invoice));
                }
            }

            $_invoiceItem = $_invoiceItem->for($_invoice)
                ->create([
                    ...$invoiceItemForeignKeyColumns,
                    'ref' => Str::orderedUuid(),
                    'type' => $this->valueOrDefault($invoice->category, InvoiceItemTypeEnum::ParticipantRegistration),
                    'price' => $invoice->price,
                    'discount' => null
                ]);
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
        Invoice::truncate();
        InvoiceItem::truncate();
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Generate the invoice name
     * 
     * @param                $invoice
     * @param  ?Charity      $charity
     * @param  ?Participant  $participant
     * @return 
     */
    private function getName($invoice, Charity $charity = null, Participant $participant = null): string
    {
        $name = 'Invoice for ';

        switch ($invoice->category) {
            case InvoiceItemTypeEnum::ParticipantRegistration->value:
                $participant->load(['eventEventCategory.event', 'user']);
                $name = $name.RegexHelper::format(InvoiceItemTypeEnum::ParticipantRegistration->name).' ('.$participant->eventEventCategory->event->name.') on behalf of '.$participant->user->full_name;
                break;

            case InvoiceItemTypeEnum::CharityMembership->value:
                $name = $name.RegexHelper::format(InvoiceItemTypeEnum::CharityMembership->name).' on behalf of '.$charity->name;
                break;
    
            case InvoiceItemTypeEnum::PartnerPackageAssignment->value:
                $name = $name.RegexHelper::format(InvoiceItemTypeEnum::PartnerPackageAssignment->name).' on behalf of '.$charity->name;
                break;
    
            case InvoiceItemTypeEnum::EventPlaces->value:
                $name = $name.RegexHelper::format(InvoiceItemTypeEnum::EventPlaces->name).' on behalf of '.$charity->name;
                break;

            case InvoiceItemTypeEnum::MarketResale->value:
                $name = $name.RegexHelper::format(InvoiceItemTypeEnum::MarketResale->name).' on behalf of '.$charity->name;
                break;

            case InvoiceItemTypeEnum::CorporateCredit->value:
                $name = $name.RegexHelper::format(InvoiceItemTypeEnum::CorporateCredit->name);
                break;
        }

        return $name;
    }
}
