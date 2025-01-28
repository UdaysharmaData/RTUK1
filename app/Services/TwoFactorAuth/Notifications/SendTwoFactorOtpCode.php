<?php

namespace App\Services\TwoFactorAuth\Notifications;

use App\Enums\TwoFactorAuthMethodEnum;
use App\Http\Helpers\MailHelper;
use App\Modules\User\Models\TwoFactorAuthMethod;
use App\Services\Auth\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class SendTwoFactorOtpCode extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

    private string $code;

    private TwoFactorAuthMethod $method;

    private string $action;


    public function __construct(TwoFactorAuthMethod $method, $code, $action = 'otp')
    {
        $this->method = $method;
        $this->code = $code;
        $this->action = $action;
    }

    /**
     * Get the notification's delivery drivers.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return [$this->driver()];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $mailHelper = new MailHelper(site());
        $ip = request()->ip();
        $mailMessage = (new MailMessage)
            ->from($mailHelper->address(), $mailHelper->name())
            ->subject($this->subjectLine()->subject)
            ->greeting("Hello {$notifiable->salutation_name},")
            ->line($this->subjectLine()->line)
            ->line("<strong>$this->code</strong>")
            ->line(Lang::get("<div>The request for this code originated from IP address <strong>$ip</strong>.</div>"))
            ->line(Lang::get("If you do not recognize this activity, please ignore this email."));
        $mailMessage->viewData = ['mailHelper' => $mailHelper];

        return $mailMessage;
    }

    /**
     * Get the sms representation of the notification
     * @param $notifiable
     * @return string
     */
    public function toSms($notifiable): string
    {
        $site = site();
        return "Your two-factor code on $site->name is: $this->code";
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    private function driver(): string
    {
        if ($this->method->name == TwoFactorAuthMethodEnum::Sms2Fa) {
            return NotificationType::Phone->value;
        } else {
            return NotificationType::Email->value;
        }
    }

    private function subjectLine(): object
    {
        switch ($this->action) {
            case 'enable':
                $subject = "Two Factor Authentication: Enable {$this->method->display_name}";
                $line = "Please enter the following code to enable two-factor authentication ({$this->method->display_name}) on your account.";
                break;
            case 'disable':
                $subject = "Two Factor Authentication: Disable {$this->method->display_name}";
                $line = "Please enter the following code to disable two-factor authentication ({$this->method->display_name}) on your account.";
                break;
            case 'login':
                $subject = "Two Factor Authentication: Login Verification";
                $line = "Please enter the following code to log into your account.";
                break;
            default:
                $subject = "Two Factor Authentication: Complete Action";
                $line = "Please enter the following code to complete the requested action.";
        }

        return (object)[
            'subject' => $subject,
            'line' => $line
        ];
    }

}
