<?php

namespace App\Mail;

use App\Traits\SiteTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Modules\User\Models\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class CharityAccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels, SiteTrait;

    public $user;
    public $site;
    public $password;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @return void
     */
    public function __construct(User $user, string $password)
    {
        $this->user = $user;
        $this->password = $password;
        $this->site = static::getSite();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from($address = config('mail.from.address'), $name = config('mail.from.name'))
            ->view('mails.account.charity')
            ->text('mails.account.plain.charity')
            ->subject("Welcome to {$this->site?->name}")
            ->with([
                'url' => \URL::to('/')
            ]);
    }
}
