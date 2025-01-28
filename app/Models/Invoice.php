<?php

namespace App\Models;

use App\Enums\InvoiceItemStatusEnum;
use Str;
use PDF;
use Carbon\Carbon;
use App\Exceptions\SiteNotFound;
use App\Http\Helpers\RegexHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Traits\FilterableListQueryScope;
use App\Models\Relations\InvoiceRelations;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Contracts\Uploadables\CanHaveUploadableResource;
use App\Contracts\Transactionables\CanHaveManyTransactionableResource;

use App\Modules\User\Models\User;
use App\Modules\Setting\Models\Site;
use App\Modules\Charity\Models\Charity;
use App\Modules\Participant\Models\Participant;

use App\Traits\SiteTrait;
use App\Traits\BelongsToSite;
use App\Traits\PdfInvoiceTrait;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\Uploadable\HasOneUpload;
use App\Traits\SiteIdAttributeGenerator;
use App\Models\Traits\InvoiceQueryScopeTrait;

use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use App\Enums\InvoiceStateEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\InvoiceTypeRefEnum;
use App\Enums\InvoiceItemTypeEnum;
use App\Http\Helpers\FormatNumber;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EntryDataService;
use App\Services\DataServices\InvoiceDataService;
use App\Services\DataServices\ParticipantDataService;
use App\Traits\Transactionable\HasManyTransactions;
use Illuminate\Support\Facades\Log;

class Invoice extends Model implements CanUseCustomRouteKeyName, CanHaveUploadableResource, CanHaveManyTransactionableResource
{
    use HasFactory,
        SiteTrait,
        SoftDeletes,
        HasOneUpload,
        BelongsToSite,
        PdfInvoiceTrait,
        HasManyTransactions,
        InvoiceRelations,
        AddUuidRefAttribute,
        UuidRouteKeyNameTrait,
        InvoiceQueryScopeTrait,
        FilterableListQueryScope,
        SiteIdAttributeGenerator;

    protected $table = 'invoices';

    protected $fillable = [
        // 'site_id', // TODO: Make this fillable later - For the General admin
        'invoiceable_id',
        'invoiceable_type',
        'po_number',
        'name',
        'description',
        'discount',
        'issue_date',
        'due_date',
        'price',
        'compute',
        'status',
        'state',
        'held',
        'send_on'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'status' => InvoiceStatusEnum::class,
        'state' => InvoiceStateEnum::class,
        'held' => 'boolean',
        'send_on' => 'date'
    ];

    protected $appends = [
        'formatted_price',
        'formatted_discount',
        'final_price',
        'formatted_final_price'
    ];

    public static $actionMessages = [
        'force_delete' => 'Deleting the invoice(s) permanently will unlink it from invoice items and others. This action is irreversible.'
    ];

    /**
     * Get the formatted price
     *
     * @return Attribute
     */
    protected function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return FormatNumber::formatWithCurrency($this->price);
            },
        );
    }

    /**
     * Get the formatted discount
     *
     * @return Attribute
     */
    protected function formattedDiscount(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return (float) $this->discount . '%';
            },
        );
    }

    /**
     * Get the final price after deducting the discount
     *
     * @return Attribute
     */
    protected function finalPrice(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->discount && $this->discount > 0)
                    return $this->price - ($this->price * ($this->discount / 100));

                return $this->price;
            },
        );
    }

    /**
     * Get the formatted final price
     *
     * @return Attribute
     */
    protected function formattedFinalPrice(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return FormatNumber::formatWithCurrency($this->final_price);
            },
        );
    }

    /**
     * The number of weeks passed since the day the invoice was sent and hasn't been paid yet.
     *
     * @param $value
     * @return Attribute
     */
    public function lateness(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $lateness = null;

                if ($this->status == InvoiceStatusEnum::Unpaid) {
                    $now = Carbon::now();

                    if ($this->date) {
                        $date = Carbon::parse($this->date);

                        if ($date->lessThanOrEqualTo($now)) {
                            $lateness = $now->diffInWeeks($date);
                        }
                    } else {
                        $lateness = $now->diffInWeeks($this->created_at);
                    }
                }

                return $lateness;
            }
        );
    }

    /**
     * @param  string|null  $value
     * @return string
     */
    public function getPoNumberAttribute(?string $value): ?string
    {
        $poNumber = $value ?: static::generatePoNumber($this);

        return $poNumber ? Str::upper($poNumber) : null;
    }

    /**
     * @param  Invoice  $invoice
     * @return void
     */
    public static function updatePoNumberField(Invoice $invoice): void
    {
        $invoice->po_number = static::generatePoNumber($invoice);
        $invoice->save();
    }

    /**
     * Generate the po_number.
     *
     * @param  Invoice  $invoice
     * @return string|null
     */
    private static function generatePoNumber(Invoice $invoice): string|null
    {
        \Log::channel('test')->debug('Generate Po Ran');

        try {
            $siteCode = self::getSiteCode();
            $date = Carbon::parse($invoice->date)->format('Ymd');

            $invoice->loadMissing('invoiceItems');

            if ($invoice->invoiceItems->count()) {
                $type = static::getTypeInitials($invoice);

                return "{$siteCode}{$type}{$date}{$invoice->id}";
            }

            return null;
        } catch (SiteNotFound $e) {
            // TODO: Log this and notify the developers through the slack channel
            return null;
        }
    }

    /**
     * Get the site code.
     *
     * @return string
     * @throws SiteNotFound
     */
    protected static function getSiteCode(): string
    {
        if ($site = Site::where('id', static::getSite()?->id)->first()) {
            return $site->code;
        }

        throw new SiteNotFound('Platform not found.');
    }

    /**
     * The initials of the invoice type.
     *
     * @param  Invoice $invoice
     * @return string
     */
    private static function getTypeInitials(Invoice $invoice)
    {
        $invoice->loadMissing('invoiceItems');

        $type = '';

        foreach ($invoice->getUniqueItemsTypes() as $_type) {
            $typeWords = preg_split("/[\s,_-]+/", trim(preg_replace('/([A-Z])/', ' $1', $_type->name)));
            $initials = '';

            foreach ($typeWords as $word) {
                $initials .= strtolower($word[0]);
            }

            $type .= $initials;
        }

        $type = &$type;

        return $type;
    }

    /**
     * compute and update the price field based on the items prices and discount.
     *
     * @param  Invoice  $invoice
     * @return Invoice
     */
    public static function updatePriceField(Invoice $invoice): Invoice
    {
        $invoice->loadMissing('invoiceItems');

        $data = [];

        if ($invoice->compute) { // Only compute the price if the compute field is set to true
            $price = 0;

            if ($invoice->invoiceItems->pluck('type')->contains(InvoiceItemTypeEnum::ParticipantTransferFee)) {
                foreach ($invoice->invoiceItems as $item) {
                    $price += ($item->type == InvoiceItemTypeEnum::ParticipantTransferOldEvent) ? -$item->finalPrice : $item->finalPrice;
                }
            } else {
                foreach ($invoice->invoiceItems as $item) {
                    $price += $item->finalPrice;
                }
            }

            $data['price'] = $price;
        }

        $data['description'] = $invoice->description ?? "Payment for the following categories: " . RegexHelper::format(implode(', ', array_column($invoice->getUniqueItemsTypes(), 'name'))) . " " . html_entity_decode("&#8212;") . " As seen on the Order Summary below:";

        $invoice->update($data);

        \Log::channel('test')->debug('Update Price Ran');

        $invoice = Invoice::generatePdf($invoice->load(['invoiceable', 'invoiceItems.invoiceItemable', 'upload']), true); // Generate a new invoice pdf everytime the price gets updated

        // CacheDataManager::flushAllCachedServiceListings(new InvoiceDataService());
        // CacheDataManager::flushAllCachedServiceListings(new EntryDataService());
        // CacheDataManager::flushAllCachedServiceListings(new ParticipantDataService());

        return $invoice;
    }

    /**
     * The unique types.
     *
     * @return array
     */
    public function getUniqueItemsTypes(): array
    {
        $this->loadMissing(('invoiceItems'));

        return $this->invoiceItems->unique('type')->pluck('type')->all();
    }

    /**
     * Generate the invoice pdf.
     *
     * @param  Invoice  $invoice
     * @param  Invoice  $regenerate // Force regenerate
     * @return Invoice
     */
    public static function generatePdf(Invoice $invoice, bool $regenerate = false): Invoice
    {
        if (!$invoice->upload || ($invoice->upload && !Storage::disk(config('filesystems.default'))->exists($invoice->upload->url)) || $regenerate) {

            $pdfData = [];

            if ($regenerate && $invoice->upload?->url && Storage::disk(config('filesystems.default'))->exists($invoice->upload->url)) { // Delete the existing pdf if it exists
                Storage::disk(config('filesystems.default'))->delete($invoice->upload->url);
            }

            $invoice->loadMissing(['invoiceable']);

            if ($invoice->invoiceable_type == Charity::class) {
                $pdfData['charity'] = $invoice->invoiceable;
                $pdfData['charity']->load(['charityOwner.user', 'latestCharityMembership']);
                $pdfData['name'] = $pdfData['charity']->name;
                $pdfData['address'] = $pdfData['charity']->location?->address ?? 'N/A';
                $pdfData['phone'] = $pdfData['charity']->finance_contact_phone ?? 'N/A';
                $pdfData['email'] = $pdfData['charity']->finance_contact_email ?? 'N/A';
                $pdfData['website'] = $pdfData['charity']->website ?? 'N/A';
            }

            if ($invoice->invoiceable_type == User::class) {
                $pdfData['user'] = $invoice->invoiceable;
                $pdfData['user']->load(['profile.participantProfile']);
                $pdfData['name'] = $pdfData['user']->full_name;
                $pdfData['address'] = $pdfData['user']->profile?->address ?? 'N/A';
                $pdfData['phone'] = $pdfData['user']->phone ?? 'N/A';
                $pdfData['email'] = $pdfData['user']->email ?? 'N/A';
                $pdfData['website'] = $pdfData['user']->profile?->participantProfile?->fundraising_url ?? 'N/A';
            }

            $pdfData['ref'] = '';

            foreach ($invoice->invoiceItems as $item) {
                if ($ref = InvoiceTypeRefEnum::tryFrom($item->type->value)?->name) {
                    $pdfData['ref'] .= $ref;
                } else if ($item->type->value == InvoiceItemTypeEnum::ParticipantTransferNewEvent->value) {
                    $pdfData['ref'] .= InvoiceTypeRefEnum::INV_PTR_->name;
                } else if ($item->type->value == InvoiceItemTypeEnum::ParticipantTransferOldEvent->value) {
                    $pdfData['ref'] .= InvoiceTypeRefEnum::INV_PTR_->name;
                }
            }

            $pdfData['ref'] .= sprintf('%10d', $invoice->id) . '_' . Carbon::parse($invoice->issue_date)->format('dmY');

            $path = static::savePDF($invoice, $pdfData);

            $upload = $invoice->upload()->updateOrCreate([],  [
                'url' => $path,
                'title' => $invoice->name,
                'type' => UploadTypeEnum::PDF,
                'private' => true
            ]);

           $invoice->uploadable()->updateOrCreate([
                'upload_id' => $upload->id,
                'use_as' => UploadUseAsEnum::PDF
            ]);
        }

        \Log::channel('test')->debug('Regenerate Invoice Ran');

        // return Invoice::with('upload')->find($invoice->id);

        return $invoice->load('upload');
    }

    /**
     * Save the pdf into storage and return the path.
     *
     * @param  Invoice  $invoice
     * @return ?string
     */
    private static function savePDF(Invoice $invoice, $pdfData): ?string
    {
        $pdfData = static::getPdfData($invoice, $pdfData);

        $pdf = PDF::loadView('pdf.invoice', $pdfData);

        $fileName = Str::random(40) . '.pdf';

        Storage::disk(config('filesystems.default'))->put(config('app.pdfs_path') . '/' . $fileName, $pdf->output(), 'private');

        $path = config('app.pdfs_path') . '/' . $fileName;

        $path = $path[0] == '/' ? Str::substr($path, 1) : $path; // Remove '/' if present at the start of path

        $pdf->download($path); // Download the file on disk

        return $path;
    }

    /**
     * Generate the name of the invoice based on some params.
     *
     * @param  InvoiceItemTypeEnum   $type
     * @param  ?Charity              $charity
     * @param  ?Participant          $participant
     * @return
     */
    public static function getFormattedName(InvoiceItemTypeEnum $type, Charity $charity = null, Participant $participant = null): string
    {
        $name = 'Invoice for ';

        switch ($type) {
            case InvoiceItemTypeEnum::ParticipantRegistration:
                if ($participant) {
                    $participant->load(['eventEventCategory.event', 'user']);
                    $name = $name . RegexHelper::format(InvoiceItemTypeEnum::ParticipantRegistration->name) . ' (' . $participant->eventEventCategory->event->name . ') on behalf of ' . $participant->user->full_name;
                } else {
                    $name = $name . RegexHelper::format(InvoiceItemTypeEnum::ParticipantRegistration->name);
                }
                break;

            case InvoiceItemTypeEnum::CharityMembership:
                $name = $name . RegexHelper::format(InvoiceItemTypeEnum::CharityMembership->name) . ' on behalf of ' . $charity->name;
                break;

            case InvoiceItemTypeEnum::PartnerPackageAssignment:
                $name = $name . RegexHelper::format(InvoiceItemTypeEnum::PartnerPackageAssignment->name) . ' on behalf of ' . $charity->name;
                break;

            case InvoiceItemTypeEnum::EventPlaces:
                $name = $name . RegexHelper::format(InvoiceItemTypeEnum::EventPlaces->name) . ' on behalf of ' . $charity->name;
                break;

            case InvoiceItemTypeEnum::MarketResale:
                $name = $name . RegexHelper::format(InvoiceItemTypeEnum::MarketResale->name) . ' on behalf of ' . $charity->name;
                break;

            case InvoiceItemTypeEnum::CorporateCredit:
                $name = $name . RegexHelper::format(InvoiceItemTypeEnum::CorporateCredit->name);
                break;
            case InvoiceItemTypeEnum::ParticipantTransferNewEvent:
                if ($participant) {
                    $name = $name . 'Participant Transfer' . ' (' . $participant->eventEventCategory->event->formattedName . ') on behalf of ' . $participant->user->full_name;
                } else {
                    $name = $name . 'Participant Transfer';
                }
                break;
            default:
                $name = $name;
                break;
        }

        return $name;
    }

    /**
     * Update the status of the invoice.
     *
     * @param  mixed $invoiceItem
     * @param  mixed $invoiceStatusEnum
     * @return Invoice
     */
    public static function updateStatus(InvoiceItem $invoiceItem, InvoiceStatusEnum $invoiceStatusEnum): Invoice
    {
        $invoice = $invoiceItem->invoice;

        if ($invoice && $invoice->invoiceItems()->where('id', '!=', $invoiceItem->id)->where('status', '!=', $invoiceItem->status)->doesntExist()) {
            $invoice->status = $invoiceStatusEnum;
            $invoice->state = InvoiceStateEnum::Complete;
        } else {
            $invoice->state = InvoiceStateEnum::Partial;
        }

        $invoice->save();

        return $invoice;
    }
}
