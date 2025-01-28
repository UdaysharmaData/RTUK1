<?php

namespace App\Listeners;

use App\Modules\Setting\Enums\SiteEnum;
use Exception;
use App\Mail\Mail;
use App\Traits\SiteTrait;
use App\Jobs\ResendEmailJob;
use Illuminate\Support\Facades\Log;
use App\Events\ParticipantNewRegistrationsEvent;
use App\Mail\participant\entry\ParticipantNewRegistrationsMail;
use App\Modules\Setting\Enums\OrganisationEnum;
use App\Modules\Setting\Models\Organisation;

class ParticipantNewRegistrationsListener
{
    use SiteTrait;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param ParticipantNewRegistrationsEvent $event
     * @return void
     */
    public function handle(ParticipantNewRegistrationsEvent $event)
    {
        $user = $event->user;
        $invoiceOrRefundTransaction = $event->invoiceOrRefundTransaction;
        $extraData = $event->extraData;
        $site = $event->site;
        $externalEnquiry = $event->externalEnquiry;
        $participantExtra = $event->participantExtra;

        if (($externalEnquiry && $externalEnquiry->origin && SiteEnum::isMainSiteInOrganization(OrganisationEnum::GWActive, SiteEnum::from($site->domain))) || ($externalEnquiry && !$externalEnquiry->origin) || !$externalEnquiry) { // Only send email from the main site of the organisation or if it's not an external enquiry or it doesn't have an origin
            try {
                Mail::site()->send(new ParticipantNewRegistrationsMail($user, $extraData, $invoiceOrRefundTransaction, $site, $externalEnquiry, $participantExtra));
            } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                Log::channel(static::getSite()?->code . 'mailexception')->info("Participants - New Registration - Create the user");
                Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                dispatch(new ResendEmailJob(new ParticipantNewRegistrationsMail($user, $extraData, $invoiceOrRefundTransaction, $site, $externalEnquiry, $participantExtra), clientSite()));
            } catch (Exception $e) {
                Log::channel(static::getSite()?->code . 'mailexception')->info("Participants - New Registration - Create the user");
                Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                dispatch(new ResendEmailJob(new ParticipantNewRegistrationsMail($user, $extraData, $invoiceOrRefundTransaction, $site, $externalEnquiry, $participantExtra), clientSite()));
            }
        }
    }
}
