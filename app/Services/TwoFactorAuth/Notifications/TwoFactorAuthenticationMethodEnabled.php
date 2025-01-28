<?php

namespace App\Services\TwoFactorAuth\Notifications;

use App\Http\Helpers\MailHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorAuthenticationMethodEnabled extends Notification
{
    use Queueable;

    private string $method;

    private $site;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($method, $site)
    {
        $this->method = $method;
        $this->site = $site;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $mailHelper = new MailHelper($this->site);
        $mailMessage = (new MailMessage)
            ->from($mailHelper->address(), $mailHelper->name())
            ->subject('Two Factor Authentication: Method Enabled')
            ->greeting("Hello {$notifiable->salutation_name},")
            ->line("<div>You have just enabled two-factor authentication through <strong>$this->method<strong/></div>.")
            ->salutation('Thanks!');

        $mailMessage->viewData = ['mailHelper' => $mailHelper];

        return $mailMessage;
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
}
