<?php

namespace App\Observers;

use App\Services\ClientOptions\EnquirySettings;
use Illuminate\Support\Facades\Log;
use App\Mail\Mail;
use App\Traits\SiteTrait;
use App\Jobs\ResendEmailJob;
use App\Models\ClientEnquiry;
use App\Modules\User\Models\User;
use App\Mail\NewContactUsEnquiry;
use App\Notifications\NewEnquiry;
use Illuminate\Support\Facades\Notification;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EnquiryDataService;

class ClientEnquiryObserver
{
    use SiteTrait;

    /**
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Enquiry "created" event.
     *
     * @param  \App\Models\ClientEnquiry  $enquiry
     * @return void
     */
    public function created(ClientEnquiry $enquiry)
    {
        $this->triggerEmailNotification($enquiry);
    }

    /**
     * Handle the Enquiry "updated" event.
     *
     * @param  \App\Models\ClientEnquiry  $enquiry
     * @return void
     */
    public function updated(ClientEnquiry $enquiry)
    {
        //
    }

    /**
     * Handle the Enquiry "deleted" event.
     *
     * @param  \App\Models\ClientEnquiry  $enquiry
     * @return void
     */
    public function deleted(ClientEnquiry $enquiry)
    {
        //
    }

    /**
     * Handle the Enquiry "restored" event.
     *
     * @param  \App\Models\ClientEnquiry  $enquiry
     * @return void
     */
    public function restored(ClientEnquiry $enquiry)
    {
        //
    }

    /**
     * Handle the Enquiry "force deleted" event.
     *
     * @param  \App\Models\ClientEnquiry  $enquiry
     * @return void
     */
    public function forceDeleted(ClientEnquiry $enquiry)
    {
        //
    }

//    /**
//     * @param ClientEnquiry $enquiry
//     * @return void
//     */
//    private function triggerNotification(ClientEnquiry $enquiry): void
//    {
//        $supportTeam = User::where('email', env('RUNTHROUGH_CLIENT_ENQUIRY_SUPPORT_ADMIN_EMAIL'))
//            ->get(); // todo: implement platform config service
//
//        Notification::send($supportTeam, new NewEnquiry($enquiry));
//    }

    /**
     * @param ClientEnquiry $enquiry
     * @return void
     */
    private function triggerEmailNotification(ClientEnquiry $enquiry): void
    {
        try {
            $settings = new EnquirySettings();

            if (! is_null($recipient = $settings->getCategoryEmail($enquiry->enquiry_type))) {
                Mail::site()
                    ->to($recipient)
                    ->send(new NewContactUsEnquiry($enquiry));
            }
        } catch (\Symfony\Component\Mailer\Exception\TransportException|\Exception $e) {
            Log::channel(static::getSite()?->code . 'mailexception')->info("Client Enquiry - Trigger Email Notification");
            Log::channel(static::getSite()?->code . 'mailexception')->info($e);
            dispatch(new ResendEmailJob(new NewContactUsEnquiry($enquiry), clientSite()));
        }
    }

//    /**
//     * @param ClientEnquiry $enquiry
//     * @return array
//     */
//    private function adminEmails(ClientEnquiry $enquiry): array
//    {
//        $categories = (array) config('apiclient.enquiries')[clientSiteCode()] ?? [];
//        $enquiryType = (string) $enquiry->enquiry_type;
//        $recipients = [];
//
//        foreach ($categories as $category) {
//            if (isset($category[$enquiryType])) {
//                $recipients[] = $category[$enquiryType];
//                break;
//            }
//        }
//        return $recipients;
//    }
}
