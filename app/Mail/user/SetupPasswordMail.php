<?php

namespace App\Mail\user;

use App\Enums\VerificationCodeTypeEnum;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Log;

class SetupPasswordMail extends UserAccountCreatedMail
{
    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            to: $this->user->email,
            subject: "Set Up Your Password"
        );
    }

    /**
     * @return Content
     * @throws \Exception
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.user.new-password-setup-instruction',
            markdown: 'mails.user.new-password-setup-instruction',
            with: [
                'user' => [
                    'name' => $this->user->salutation_name,
                    'email' => $this->user->email,
                    'code' => $this->user->generateVerificationCode(VerificationCodeTypeEnum::PasswordSetup),
                ],
                'member' => $this->mailHelper->topExecutiveMember()
            ]
        );
    }
}
