<?php

namespace App\Services\Auth\Notifications;

use App\Enums\VerificationCodeTypeEnum;
use App\Http\Helpers\MailHelper;
use App\Modules\User\Models\VerificationCode;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Services\Auth\Enums\NotificationType;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

class SendVerificationCode extends Notification
{
    use Queueable;

    private array $drivers = [];

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        public NotificationType $type,
        public string $action,
        public ?string $token = null,
    ){
        $this->drivers[] = $this->type->value;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via(mixed $notifiable): array
    {
        return $notifiable->prefers_sms ? ['vonage'] : [...$this->drivers];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     * @throws Exception
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $mailHelper = new MailHelper(site());
        if ($mailHelper->name() == 'RunThrough' || $mailHelper->name() == 'Runthrough') {
            $mail_credentials = config('services.rtuk_mail_credentials');
            Config::set('mail.mailers.smtp.transport', 'smtp');
            Config::set('mail.mailers.smtp.host', $mail_credentials['RUNTHROUGH_MAIL_HOST']);
            Config::set('mail.mailers.smtp.port', $mail_credentials['RUNTHROUGH_MAIL_PORT']);
            Config::set('mail.mailers.smtp.username', $mail_credentials['RUNTHROUGH_MAIL_USERNAME']);
            Config::set('mail.mailers.smtp.password', $mail_credentials['RUNTHROUGH_MAIL_PASSWORD']);
            Config::set('mail.mailers.smtp.encryption', $mail_credentials['RUNTHROUGH_MAIL_ENCRYPTION']);
        }
        $subjectLine = $this->subjectLine();
        $message = (new MailMessage)
            ->subject($subjectLine->subject)
            ->from($mailHelper->address(), $mailHelper->name())
            ->greeting("Hello $notifiable->salutation_name,")
            ->Line("$subjectLine->line1")
            ->line("$subjectLine->line2")
            ->line("<strong>{$notifiable->generateVerificationCode($subjectLine->verificationCodeTypeEnum)}</strong>")
            ->line($subjectLine->line3);
        $message->viewData = ['mailHelper' => $mailHelper];

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }

    public function toSms($notifiable): string
    {
        $site = site();
        return "Your OTP code on $site->name is: {$notifiable->generateVerificationCode()}";
    }

    private function subjectLine(): object
    {
        switch ($this->action) {
            case 'verify_email':
                $subject = 'Verify Your Email Address';
                $line1 = 'Your account registration was successful. <br/>Quickly unlock the full-range of personalized features and services on our platform by verifying your account.';
                $line2 = 'Please enter the 6-digit code below when prompted on the verification page. ' . VerificationCode::getValidityMessage();
                $line3 = 'If you did not attempt to create an account, no further action is required.';
                $verificationCodeTypeEnum = VerificationCodeTypeEnum::AccountVerification;
                break;

            case 'password_reset':
                $subject = "Reset Your Password";
                $line1 = 'You have requested to reset your password.';
                $line2 = 'Please enter the 6-digit code below when prompted on the reset page. ' . VerificationCode::getValidityMessage();
                $line3 = 'If you did not attempt to reset your password, no further action is required.';
                $verificationCodeTypeEnum = VerificationCodeTypeEnum::PasswordReset;
                break;
            case 'set_password':
                $subject = "Account Created [Password Set Up Required]";
                $line1 = 'Welcome aboard!';
                $line2 = 'To get started, you\'ll need to set up a secure password for your account.';
                $line3 = 'Please enter the 6-digit code above when prompted on the password set up page to proceed. ' . VerificationCode::getValidityMessage();
                $verificationCodeTypeEnum = VerificationCodeTypeEnum::PasswordSetup;
                break;
        }

        return (object) [
            'subject' => $subject,
            'line1' => $line1,
            'line2' => $line2,
            'line3' => $line3,
            'verificationCodeTypeEnum' => $verificationCodeTypeEnum
        ];
    }
}
