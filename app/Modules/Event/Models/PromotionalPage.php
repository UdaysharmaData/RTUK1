<?php

namespace App\Modules\Event\Models;

use App\Models\Region;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Enums\PromotionalPageTypeEnum;
use Illuminate\Database\Eloquent\Model;
use App\Enums\PromotionalPagePaymentOptionEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromotionalPage extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'promotional_pages';

    protected $fillable = [
        'event_page_listing_id',
        'title',
        'slug',
        'type',
        'region_id',
        'payment_option',
        'event_page_background_image'
    ];

    protected $casts = [
        'type' => PromotionalPageTypeEnum::class,
        'payment_option' => PromotionalPagePaymentOptionEnum::class
    ];

    /**
     * Get the region.
     * 
     * @return BelongsTo
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the event page listing.
     * @return BelongsTo
     */
    public function eventPageListing(): BelongsTo
    {
        return $this->belongsTo(EventPageListing::class);
    }

    /**
     * Create an event page and link it to the event page listing of the promotional event.
     * 
     * @param  Event $event
     * @return EventPage
     */
    public function createEventPage(Event $event): EventPage
    {
        $eventPage = new EventPage();

        // $eventPage->event_id = $event->id;
        $eventPage->charity_id = $this->eventPageListing->charity->id;
        // $eventPage->site_id = (int) Session::get('selectedSite');
        $eventPage->published = true;
        // $eventPage->fundraising_target = $event->registration_price;
        $eventPage->fundraising_title = "Registration Fee";
        $eventPage->fundraising_description = $this->description;
        // $eventPage->background_image = $this->event_page_background_image;
        $eventPage->payment_option = $this->payment_option->value;

        if ($this->type == PromotionalPageTypeEnum::PromotionalPage1) {
            // $eventPage->form_text = "By registering your interest for this event you will be asked to pay for the place you have selected. All charities are required to pay for places in the top events in the country and some charities do not have the money to pay for them in advance. The payment you are asked to make will directly correspond to the amount that your charity is required to pay. Thank you for your support.";
        }

        do {
            $eventPage->code = rand(0, 999999);
        } while(EventPage::codeAlreadyUsed($eventPage->code));

        $eventPage->save();

        // Attach the event page to it's event(s)
        $eventPage->events()->syncWithoutDetaching($event->id);

        return $eventPage;
    }

}
