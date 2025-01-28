<?php

namespace App\Traits;

use Log;
use App\Mail\Mail;
use App\Mail\MailLayout;
use App\Jobs\ResendEmailJob;
use App\Http\Helpers\MailHelper;
use Illuminate\Mail\Mailables\Address;

trait AdministratorEmails
{
    use SiteTrait;

    /**
     * @param MailLayout $mailLayout
     * @return void
     */
    public static function sendEmails(MailLayout $mailLayout): void
    {
        $mailHelper = new MailHelper();
        $administrators = $mailHelper->administrators();

        foreach ($administrators as $administrator) {
            $mailLayout->to = [];

            try {
                Mail::site()->to(new Address($administrator->email, $administrator->salutation_name))->send($mailLayout);
            } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                Log::channel(static::getSite()?->code . 'mailexception')->info("Participant - Transfer - Refund");
                Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                dispatch(new ResendEmailJob($mailLayout->to(new Address($administrator->email, $administrator->salutation_name)), clientSite()));
            } catch (\Exception $e) {
                Log::channel(static::getSite()?->code . 'mailexception')->info("Participant - Transfer - Refund");
                Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                dispatch(new ResendEmailJob($mailLayout->to(new Address($administrator->email, $administrator->salutation_name)), clientSite()));
            }
        }
    }
}
