<?php

namespace App\Services\Auth\Traits;

use Log;
use Exception;
use App\Mail\Mail;
use App\Traits\SiteTrait;
use App\Jobs\ResendEmailJob;
use App\Mail\user\SetupPasswordMail;
use App\Services\Auth\Enums\NotificationType;
use App\Mail\user\UserAccountCreatedByAdminMail;
use App\Services\Auth\Notifications\SendVerificationCode;

trait MustVerifyAccountEmail
{
    use SiteTrait;

    /**
     * Determine if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    /**
     * Determine if the user has set up a password.
     *
     * @return bool
     */
    public function hasSetPassword(): bool
    {
        return ! is_null($this->password);
    }

    /**
     * Mark the given user's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Get the email address that should be used for verification.
     *
     * @return string
     */
    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     * @throws Exception
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new SendVerificationCode(NotificationType::Email, 'verify_email'));
    }

    /**
     * Send password set up notification.
     *
     * @param bool $resend
     * @return void
     */
    public function sendPasswordSetUpNotification(bool $resend = false): void
    {
//        $this->notify(new SendVerificationCode(NotificationType::Email, 'set_password'));
        if ($resend) {
            try {
                Mail::site()->send(new SetupPasswordMail($this));
            } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                Log::channel(static::getSite()?->code . 'mailexception')->info("User - Resend Password Setup Notification");
                Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                dispatch(new ResendEmailJob(new SetupPasswordMail($this), clientSite()));
            } catch (Exception $e) {
                Log::channel(static::getSite()?->code . 'mailexception')->info("User - Resend Password Setup Notification");
                Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                dispatch(new ResendEmailJob(new SetupPasswordMail($this), clientSite()));
            }
        } else {
            try {
                Mail::site()->send(new UserAccountCreatedByAdminMail($this));
            } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                Log::channel(static::getSite()?->code . 'mailexception')->info("User - Send Password Setup Notification");
                Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                dispatch(new ResendEmailJob(new UserAccountCreatedByAdminMail($this), clientSite()));
            } catch (Exception $e) {
                Log::channel(static::getSite()?->code . 'mailexception')->info("User - Send Password Setup Notification");
                Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                dispatch(new ResendEmailJob(new UserAccountCreatedByAdminMail($this), clientSite()));
            }
        }
    }
}
