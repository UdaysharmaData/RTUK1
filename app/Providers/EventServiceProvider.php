<?php

namespace App\Providers;

use App\Models\City;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\Region;
use App\Models\Venue;
use App\Modules\User\Models\Profile;
use App\Modules\User\Models\Role;
use App\Observers\CityObserver;
use App\Observers\PageObserver;
use App\Observers\ProfileObserver;
use App\Observers\RedirectObserver;
use App\Observers\RegionObserver;
use App\Observers\RoleObserver;
use App\Observers\VenueObserver;
use App\Events\EventsArchivedEvent;
use Illuminate\Auth\Events\Registered;
use App\Listeners\EventsArchivedListener;
use App\Events\ParticipantNewRegistrationsEvent;
use App\Services\Analytics\Events\AnalyticsViewEvent;
use App\Services\Analytics\Listeners\AnalyticsViewHandler;
use App\Services\Analytics\Events\AnalyticsInteractionEvent;
use App\Services\Analytics\Listeners\AnalyticsInteractionHandler;
use App\Services\Auth\Listeners\SendAccountVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use App\Listeners\ParticipantNewRegistrationsListener;

use App\Models\Medal;
use App\Models\Invoice;
use App\Models\Sitemap;
use App\Models\Experience;
use App\Models\InvoiceItem;
use App\Models\Combination;
use App\Models\ClientEnquiry;
use App\Modules\User\Models\User;
use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\Serie;
use App\Modules\Setting\Models\Site;
use App\Modules\Event\Models\Sponsor;
use App\Modules\Charity\Models\Charity;
use App\Modules\Partner\Models\Partner;
use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\Event\Models\EventCategory;
use App\Modules\User\Models\VerificationCode;
use App\Modules\Participant\Models\Participant;
use App\Modules\Enquiry\Models\ExternalEnquiry;

use App\Observers\SiteObserver;
use App\Observers\UserObserver;
use App\Observers\MedalObserver;
use App\Observers\SerieObserver;
use App\Observers\SponsorObserver;
use App\Observers\InvoiceObserver;
use App\Observers\SitemapObserver;
use App\Observers\ExperienceObserver;
use App\Observers\InvoiceItemObserver;
use App\Observers\CombinationObserver;
use App\Observers\ClientEnquiryObserver;
use App\Observers\VerificationCodeObserver;
use App\Modules\Event\Observers\EventObserver;
use App\Modules\Enquiry\Observers\EnquiryObserver;
use App\Modules\Partner\Observers\PartnerObserver;
use App\Modules\Charity\Observers\CharityObserver;
use App\Modules\Event\Observers\EventCategoryObserver;
use App\Modules\Participant\Observers\ParticipantObserver;
use App\Modules\Enquiry\Observers\ExternalEnquiryObserver;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Observers\AccountObserver;
use App\Modules\Partner\Models\PartnerChannel;
use App\Modules\Partner\Observers\PartnerChannelObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        EventsArchivedEvent::class => [
            EventsArchivedListener::class
        ],
        ParticipantNewRegistrationsEvent::class => [
            ParticipantNewRegistrationsListener::class
        ],
        Registered::class => [
            SendAccountVerificationNotification::class,
        ],
        AnalyticsViewEvent::class => [
            AnalyticsViewHandler::class,
        ],
        AnalyticsInteractionEvent::class => [
            AnalyticsInteractionHandler::class,
        ]
    ];

    /**
     * The model observers for your application.
     *
     * @var array
     */
    protected $observers = [
        VerificationCode::class => [VerificationCodeObserver::class],
        ClientEnquiry::class => [ClientEnquiryObserver::class],
        Enquiry::class => [EnquiryObserver::class],
        ExternalEnquiry::class => [ExternalEnquiryObserver::class],
        Invoice::class => [InvoiceObserver::class],
        InvoiceItem::class => [InvoiceItemObserver::class],
        Sitemap::class => [SitemapObserver::class],
        Site::class => [SiteObserver::class],
        Combination::class => [CombinationObserver::class],
        Charity::class => [CharityObserver::class],
        EventCategory::class => [EventCategoryObserver::class],
        Experience::class => [ExperienceObserver::class],
        Account::class => [AccountObserver::class],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        User::observe(UserObserver::class);
        Role::observe(RoleObserver::class);
        //Profile::observe(ProfileObserver::class);
        Page::observe(PageObserver::class);
        Medal::observe(MedalObserver::class);
        Venue::observe(VenueObserver::class);
        City::observe(CityObserver::class);
        Region::observe(RegionObserver::class);
        Event::observe(EventObserver::class);
        Serie::observe(SerieObserver::class);
        Sponsor::observe(SponsorObserver::class);
        Participant::observe(ParticipantObserver::class);
        Partner::observe(PartnerObserver::class);
        Redirect::observe(RedirectObserver::class);
        PartnerChannel::observe(PartnerChannelObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
