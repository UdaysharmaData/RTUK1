<?php

namespace App\Mail\charity\membership;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

use App\Mail\MailLayout;
use App\Modules\User\Models\User;

class CharityAccountCreatedMail extends MailLayout
{

    private User $user;

    private string $password;

    public function __construct(User $user, string $password, $site = null)
    {
        $this->user = $user;
        $this->password = $password;

        parent::__construct($site);
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
          from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
          to: $this->user->email,
          subject: "Your {$this->mailHelper->site->name} Login"
        );
    }

    public function content(): Content
    {
        return new Content(
          view: 'mails.charity.membership.new',
          markdown: 'mails.charity.membership.new',
          with: [
              'user' => [
                  'name' => $this->user->salutation_name,
                  'email' => $this->user->email,
                  'password' => $this->password
              ]
            ]
        );
    }


}
