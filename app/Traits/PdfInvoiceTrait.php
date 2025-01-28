<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\Invoice;
use App\Enums\InvoiceItemTypeEnum;
use App\Modules\Charity\Models\Charity;
use App\Enums\EventPlaceInvoicePeriodInMonthRangeTextEnum;
use App\Modules\Event\Models\EventEventCategory;

trait PdfInvoiceTrait
{
    /**
     * Get the pdf data.
     *
     * @param  Invoice $invoice
     * @param  array   $pdfData
     * @return ?array
     */
    protected static function getPdfData(Invoice $invoice, array $pdfData): ?array
    {
        $name = $pdfData['name'];
        $title = $invoice->name;
        $logo = 'https://runthrough.runthroughhub.com/assets/images/logo.png';
        $issueDate = $invoice->issue_date->format('F d, Y');
        $dueDate = $invoice->due_date->format('F d, Y');
        $description = $invoice->description;

    //    Information content
        $headerInfo = [
            ['value' => 'ID'],
            ['value' => 'Status'],
            ['value' => 'Issue Date'],
            ['value' => 'Due Date'],
            ['value' => 'Billing Address'],
        ];

        $class = $invoice->status->value == 'paid' ? 'success' : 'danger';

        $bodyInfo = [
            [
                ['value' => $invoice->po_number, 'className' => 'break_word'],
                ['value' => "<span class='pill {$class}'>{$invoice->status->name}</span>"],
                ['value' => $issueDate],
                ['value' => $dueDate],
                ['value' => "<div class='item'>
                            <span>{$pdfData["address"]}</span><br />
                            <span>Phone: <a href='tel:{$pdfData["phone"]}' data-site='runthrough'>{$pdfData["phone"]}</a></span><br />
                            <span>Email: <a href='mailto:{$pdfData["email"]}' data-site='runthrough'>{$pdfData["email"]}</a></span><br />
                            <span>Website: <a href='{$pdfData["website"]}' data-site='runthrough'>{$pdfData["website"]}</a></span>
                        </div>"],
            ]
        ];

    //    Summary Content
        $headerSummary = [
            ['value' => '#'],
            ['value' => 'Item'],
            ['value' => 'Quantity', 'className' => 'text__center'],
            ['value' => 'Price', 'className' => 'text__center'],
            ['value' => 'Discount', 'className' => 'text__center'],
            ['value' => 'Total', 'className' => 'text__center'],
            ['value' => 'Status', 'className' => 'text__center']
        ];

        $bodySummary = [];

        foreach ($invoice->invoiceItems as $key => $item) { // TODO: Move this to a static function or a trait.
            $note = null;

            if ($item->invoice_itemable_type == Charity::class) { // This is used because the data seeded in the invoice_items table was associated with the Charity model for the types charity_membership, market_resale, event_places, partner_package_assignment since we could not get the exact resource to associate each item with it's corresponding model.
                $note = "For: {$item->invoiceItemable->name}";
            } else {
                if ($item->type->name == InvoiceItemTypeEnum::CharityMembership->name) {
                    $charityMembership = $item->invoiceItemable->load(['charity.charityOwner.user']);

                    $renewalDate = Carbon::parse($charityMembership->expiry_date)->addDay();
                    $note = "Type: {$charityMembership->type->name} | Renewed On: {$charityMembership->renewed_on} | Start Date: {$charityMembership->start_date} | Expiry Date: {$charityMembership->expiry_date} | Renewal Date: {$renewalDate}";
                }

                if ($item->type->name == InvoiceItemTypeEnum::MarketResale->name) {
                    $resaleRequest = $item->invoiceItemable->load(['charity.charityOwner.user', 'resalePlace']);

                    $note = "Offered By: [{$resaleRequest->resalePlace->charity->name}]";
                }

                if ($item->type->name == InvoiceItemTypeEnum::ParticipantRegistration->name) {
                    if ($item->invoiceItemable instanceof EventEventCategory) {
                        $item = $item->load(['invoiceItemable.event' => function ($query) {
                                $query->withTrashed()
                                    ->withDrafted();
                            }, 'invoiceItemable.eventCategory' => function ($query) {
                                $query->withTrashed();
                            }
                        ]);

                        $note = "<div>
                                <span>Event: {$item->invoiceItemable->event->formattedName}</span><br />
                                <span>Category: {$item->invoiceItemable->eventCategory?->name}</span>
                            </div>";
                    } else {
                        $item = $item->load(['invoiceItemable' => function ($query) {
                                $query->withTrashed();
                            }, 'invoiceItemable.charity', 'invoiceItemable.eventEventCategory.event' => function ($query) {
                                $query->withTrashed();
                            }, 'invoiceItemable.eventEventCategory.eventCategory' => function ($query) {
                                $query->withTrashed();
                            }
                        ]);

                        $participant = $item->invoiceItemable;

                        $note = "<div>
                                    <span>Event: {$participant->eventEventCategory->event->formattedName}</span><br />
                                    <span>Category: {$participant->eventEventCategory->eventCategory?->name}</span><br />".($participant->charity ? "
                                    <span>Charity: " . $participant->charity->name . '</span>' : null)."
                                </div>";
                    }
                }

                if ($item->type->name == InvoiceItemTypeEnum::EventPlaces->name) {
                    $eventPlaces = $item->invoiceItemable->load(['charity']);

                    $note = "For: {$eventPlaces->charity->name} | Period: {EventPlaceInvoicePeriodInMonthRangeTextEnum::from($eventPlaces->period)}";
                }

                if ($item->type->name == InvoiceItemTypeEnum::ParticipantTransferOldEvent->name) {
                    $participant = $item->invoiceItemable->load(['event', 'eventEventCategory']);

                    $note = "<div>
                                <span>Event: {$item->invoiceItemable->event->formattedName}</span><br />
                                <span>Category: {$item->invoiceItemable->eventEventCategory->eventCategory?->name}</span>
                            </div>";
                    $participantTransfer = true;
                }

                if ($item->type->name == InvoiceItemTypeEnum::ParticipantTransferNewEvent->name) {
                    $participant = $item->invoiceItemable->load(['event', 'eventEventCategory']);

                    $note = "<div>
                                <span>Event: {$item->invoiceItemable->event->formattedName}</span><br />
                                <span>Category: {$item->invoiceItemable->eventEventCategory->eventCategory?->name}</span>
                            </div>";
                }
            }

            $class = $item['status']->value == 'paid' ? 'success' : 'danger';

            $bodySummary = [...$bodySummary, [
                    ['value' => $key+1, 'className' => 'text__bold'],
                    ['value' => "<div class='item'>
                                    <span class='item__title'>{$item['type']->formattedName()}</span><br />
                                    <span class='f-12'>{$note}</span>
                                </div>"],
                    ['value' => '1', 'className' => 'text__center'],
                    ['value' => "{$item["formatted_price"]}", 'className' => 'text__center'],
                    ['value' => $item["formatted_discount"] ?? 'N/A', 'className' => 'text__center'],
                    ['value' => "{$item["formatted_final_price"]}", 'className' => 'text__center text__bold'],
                    ['value' => "<span class='pill {$class}'>{$item["status"]->name}</span>", 'className' => 'text__center']
                ]
            ];
        }

        // if (isset($pdfData['participant'])) { // Note: Update this code to display the family registrations once the family registration part would be available
        //     $bodySummary = [
        //         [
        //             ['value' => '1', 'className' => 'text__bold'],
        //             ['value' => '<div class="item">
        //                             <span class="item__title">{$type}</span>
        //                             <span>{$type}.</span>
        //                         </div>'],
        //             ['value' => '1', 'className' => 'text__center'],
        //             ['value' => '{$pdfData["formatted_price"]}', 'className' => 'text__center'],
        //             ['value' => '{$pdfData["formatted_price"]}', 'className' => 'text__center text__bold']
        //         ]
        //     ];
        // }

    //    Details content
        $headerDetails = [
            ['value' => 'Bank'],
            ['value' => 'Account Number'],
            ['value' => 'Sort Code'],
            ['value' => 'Sub-total', 'className' => 'text__center'],
            ['value' => 'Discount', 'className' => 'text__center'],
            ['value' => 'Total', 'className' => 'text__center']
        ];

        $explanation = null;

        if (isset($participantTransfer) && abs($invoice->price) != $invoice->price) {
            $explanation = "(Credited to your wallet)";
        }

        $bodyDetails = [
            [
                ['value' => 'Lloyds', 'className' => 'text__bold'],
                ['value' => '20431368', 'className' => 'text__bold'],
                ['value' => '30–97–86', 'className' => 'text__bold'],
                ['value' => "{$invoice->formatted_price}", 'className' => 'text__center'],
                ['value' => "{$invoice->formatted_discount}", 'className' => 'text__center'],
                ['value' => "{$invoice->formatted_final_price} $explanation", 'className' => 'text__center text__bold']
            ]
        ];

        $note = 'Payments are due 14 days from the receipt of this invoice and payable to <strong data-site="runthrough">RunThrough (Company No: 08343864), 33 Wood St, Barnet EN5 4BE</strong>.';

        return [
            'title' => $title,
            'logo' => $logo,
            'name' => $name,
            'description' => $description,
            'headerInfo' => $headerInfo,
            'bodyInfo' => $bodyInfo,
            'headerDetails' => $headerDetails,
            'bodyDetails' => $bodyDetails,
            'headerSummary' => $headerSummary,
            'bodySummary' => $bodySummary,
            'issueDate' => $issueDate,
            'dueDate' => $dueDate,
            'note' => $note,
            'explanation' => $explanation
        ];
    }
}