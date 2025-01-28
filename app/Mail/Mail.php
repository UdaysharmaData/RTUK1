<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;

use Exception;
use App\Traits\Response;
use App\Traits\SiteTrait;
use Illuminate\Queue\SerializesModels;
use App\Modules\Setting\Models\Setting\Site;
use Illuminate\Support\Facades\Mail as ParentMail;

class Mail extends ParentMail
{
    use SiteTrait,
        Response,
        Queueable,
        SerializesModels;

    public $site;

    /**
     * Get the site from which the email should be sent.
     *
     * @param  Site  $site
     * @return \Illuminate\Contracts\Mail\Mailer
     */
    public static function site($site = null): \Illuminate\Contracts\Mail\Mailer
    {
        if (!$site) {
            $site = static::getSite();

            if (!$site) {
                throw new Exception('The site was not found!');
            }
        }

        if (!config('mail.mailers.' . $site->code)) {
            throw new Exception("The mail configuration for {$site->name} is invalid!");
        }

        return ParentMail::mailer($site->code);
    }
}
