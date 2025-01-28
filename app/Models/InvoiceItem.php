<?php

namespace App\Models;

use Carbon\Carbon;
use App\Http\Helpers\RegexHelper;
use App\Http\Helpers\FormatNumber;
use App\Enums\InvoiceItemTypeEnum;
use App\Traits\AddUuidRefAttribute;
use App\Enums\InvoiceItemStatusEnum;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Models\Relations\InvoiceItemRelations;
use App\Traits\UseDynamicallyAppendedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\EventPlaceInvoicePeriodInMonthRangeTextEnum;
use App\Contracts\Transactionables\CanHaveManyTransactionableResource;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\ResaleRequest;
use App\Modules\Participant\Models\Participant;
use App\Modules\Charity\Models\EventPlaceInvoice;
use App\Modules\Charity\Models\CharityMembership;
use App\Modules\Charity\Models\CharityPartnerPackage;
use App\Modules\Event\Models\EventEventCategory;
use App\Traits\Transactionable\HasManyTransactions;

class InvoiceItem extends Model implements CanHaveManyTransactionableResource
{
    use HasFactory,
        InvoiceItemRelations,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        UseDynamicallyAppendedAttributes,
        HasManyTransactions;

    protected $table = 'invoice_items';

    protected $fillable = [
        'invoice_id',
        'invoice_itemable_id',
        'invoice_itemable_type',
        'type',
        'status',
        'discount',
        'price'
    ];

    protected $casts = [
        'type' => InvoiceItemTypeEnum::class,
        'status' => InvoiceItemStatusEnum::class
    ];

    protected $appends = [
        'formatted_price',
        'formatted_discount',
        'final_price',
        'formatted_final_price'
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
                return (double) $this->discount.'%';
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
     * Get the formatted type
     *
     * @return Attribute
     */
    protected function formattedType(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->type->formattedName();
            },
        );
    }

    /**
     * Get the note
     *
     * @return Attribute
     */
    protected function note(): Attribute
    {
        return Attribute::make(
            get: function () { // TODO: Revise the InvoiceSeeder and ensure the invoice_itemable_type gets linked to the model corresponding to it's type (charity_membership, market_resale etc)

                $value = [];

                if ($this->invoice_itemable_type == Charity::class) { // This is used because the data seeded in the invoice_items table was associated with the Charity model for the types charity_membership, market_resale, event_places, partner_package_assignment since we could not get the exact resource to associate each item with it's corresponding model.
                    $value = [...$value, "For: {$this->invoiceItemable?->name}"];
                } else {
                    switch ($this->type) {
                        case InvoiceItemTypeEnum::CharityMembership:
                            $this->load(['invoiceItemable']);
                            $charityMembership = $this->invoiceItemable;

                            $renewalDate = Carbon::parse($charityMembership?->expiry_date)->addDay();

                            $value = [...$value, "Type: {$charityMembership?->type->name}"];
                            $value = [...$value, "Renewed On: {$charityMembership?->renewed_on}"];
                            $value = [...$value, "Start Date: {$charityMembership?->start_date}"];
                            $value = [...$value, "Expiry Date: {$charityMembership?->expiry_date}"];
                            $value = [...$value, "Renewal Date: {$renewalDate}"];
                            break;

                        case InvoiceItemTypeEnum::MarketResale:
                            $this->load(['invoiceItemable']);
                            $resaleRequest = $this->invoiceItemable->load('charity');

                            $value = [...$value, "Offered By: [{$resaleRequest?->resalePlace->charity->name}]"];
                            break;

                        case InvoiceItemTypeEnum::ParticipantRegistration:
                            // $this->load(['invoiceItemable']); // This note accessor creates an infinite loop when it loads the Participant model. This is because the formatted_status accessor on the Participant model also loads this InvoiceItem model. Avoid loading the invoiceItemable relationship here when the invoice_itemable_type == Participant::class.
                            // $participant = $this->invoiceItemable->load(['charity', 'event', 'eventCategory']);

                            if ($this->invoiceItemable instanceof EventEventCategory) {
                                $value = [...$value, "User: {$this->invoice->invoiceable->full_name}", "Event: {$this->invoiceItemable->event->formattedName}", "Category: {$this->invoiceItemable->eventCategory?->name}"];
                            } else {
                                $participant = $this->invoiceItemable()
                                    ->with(['charity', 'eventEventCategory.event' => function ($query) {
                                            $query->withTrashed();
                                        }, 'eventEventCategory.eventCategory', 'user' => function ($query) {
                                            $query->withTrashed();
                                        }])
                                    ->where('id', $this->invoice_itemable_id)
                                    ->first();

                                $value = [...$value, "User: {$participant?->user->full_name}"];
                                $value = [...$value, "Event: {$participant?->eventEventCategory->event->formattedName}"];
                                $value = [...$value, "Category: {$participant?->eventEventCategory->eventCategory?->name}"];
                                $value = [...$value, $participant?->charity ? "Charity: ".$participant?->charity->name : null];
                            }
                            break;

                        case InvoiceItemTypeEnum::EventPlaces:
                            $this->load(['invoiceItemable']);
                            $eventPlaces = $this->invoiceItemable->load(['charity']);

                            $value = [...$value, "For: {$eventPlaces?->charity->name}]"];
                            $value = [...$value, "Period: ". RegexHelper::format(EventPlaceInvoicePeriodInMonthRangeTextEnum::from($eventPlaces?->period)->name)];
                            $value = [...$value, "Status: {$this->invoice?->status->name}"];
                            break;

                        case InvoiceItemTypeEnum::PartnerPackageAssignment:
                            $value = null;
                            break;

                        case InvoiceItemTypeEnum::CorporateCredit:
                            $value = null;
                            break;

                        default;
                            $value = null;
                            break;
                    }
                }

                return $value;
            }
        );
    }

    /**
     * Load invoice item relations then get the label
     *
     * @param   string  $class
     * @param           $object
     * @return  ?string
     */
    public function loadRelationsThenGetLabel(): ?string
    {
        switch ($this->invoice_itemable_type)
        {
            case CharityMembership::class:
                $object = $this->invoiceItemable->load('charity');
                break;
            case Participant::class:
                $object = $this->invoiceItemable;
                break;
            case CharityPartnerPackage::class: 
                $object = $this->invoiceItemable->load(['charity', 'partnerPackage']);
                break;
            case ResaleRequest::class: 
                $object = $this->invoiceItemable->load(['charity', 'resalePlace']);
                break;
            case EventPlaceInvoice::class: 
                $object = $this->invoiceItemable->load('charity');
                break;
            default:
                $object = null;
                break;
        }

        return static::getLabel($this->invoice_itemable_type, $object);
    }

    /**
     * Get the invoice item label
     *
     * @param   string  $class
     * @param           $object
     * @return  ?string
     */
    public static function getLabel(string $class, $object): ?string
    {
        switch ($class)
        {
            case CharityMembership::class:
                $label = "{$object->charity->name} - {$object->type->name} - {$object->renewed_on->format('d/m/Y')} - {$object->expiry_date->format('d/m/Y')}";
                break;
            case Participant::class:
                $label = $object->custom_name;
                break;
            case CharityPartnerPackage::class: 
                $label = "{$object->charity->name} - {$object->partnerPackage->name} - {$object->partnerPackage->start_date->format('d/m/Y')} - {$object->partnerPackage->end_date->format('d/m/Y')}";
                break;
            case ResaleRequest::class: 
                $label = "{$object->charity->name} - {$object->resalePlace->event->formattedName} - {$object->resalePlace->places}";
                break;
            case EventPlaceInvoice::class: 
                $label = "{$object->charity->name} - ". RegexHelper::format(EventPlaceInvoicePeriodInMonthRangeTextEnum::from($object->period)->name ." - {$object->year}");
                break;
            default:
                $label = null;
                break;
        }

        return $label;
    }
}
