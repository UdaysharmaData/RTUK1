<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\CharitySignup;

class CharitySignupMail extends Mailable
{
    use Queueable, SerializesModels;

    public $enquiry;

    /**
     * Create a new message instance.
     *
     * @param CharitySignup $enquiry
     * @return void
     */
    public function __construct(CharitySignup $enquiry)
    {
        $this->enquiry = $enquiry;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from($address= config('mail.from.address'), $name=config('mail.from.name'))
            ->view('mails.account.charity-signup')
            ->text('mails.account.plain.charity-signup')
            ->subject('New Charity Sign Up');
    }
}
