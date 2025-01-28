<?php

namespace App\Notifications;

use App\Models\ClientEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewEnquiry extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(protected ClientEnquiry $enquiry)
    {}

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailLayout)
                    ->subject("New Enquiry - {$this->enquiry->enquiry_type}")
                    ->replyTo($this->enquiry->email)
                    ->greeting("Hi, $notifiable->first_name!")
                    ->line("You have a new enquiry from {$this->enquiry->full_name}:")
                    ->line($this->enquiry->message)
//                    ->action('Notification Action', url("/enquiries/{$this->enquiry->ref}"))
                    ;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'enquiry' => $this->enquiry,
        ];
    }
}
