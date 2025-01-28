<?php

namespace App\Mail\user;

use App\Enums\VerificationCodeTypeEnum;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Support\Facades\Log;

class UserAccountCreatedByAdminMail extends UserAccountCreatedMail
{
    /**
     * @return Content
     * @throws \Exception
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.user.new-by-admin',
            markdown: 'mails.user.new-by-admin',
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
