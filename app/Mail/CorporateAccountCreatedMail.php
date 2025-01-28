<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CorporateAccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from($address = config('mail.from.address'), $name = config('mail.from.name'))
            ->view('mails.account.corporate')
            ->text('mails.account.plain.corporate')
            ->subject('Welcome to Sport For Charity')
            ->with([
                'url' => \URL::to('/')
            ]);
    }
}
