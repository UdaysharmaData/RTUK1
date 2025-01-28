<?php

namespace App\Notifications;

use App\Http\Helpers\MailHelper;
use App\Http\Middleware\Authenticate;
use App\Mail\ExportedListingDataAttachmentMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ExportedListingDataAttachmentReadyNotification extends Notification
{
    use Queueable;

    public MailHelper $mailHelper;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        protected array $attachment,
        protected Authenticatable $user,
        protected  $site,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return ExportedListingDataAttachmentMail
     */
    public function toMail($notifiable)
    {
        $mailer = (new ExportedListingDataAttachmentMail($this->attachment['s3PathLink'], $this->user->first_name, $this->site))
            ->to($this->user->email)
            ->subject('Exported Data Ready')
            ->mailer($this->site->code);

        if (config('filesystems.default') !== 's3') {
            $headers = [
                'as' => $this->attachment['file_name']
                    ?? 'exported-listing-data.csv-' . now()->format('Y-m-d-H-i-s'),
                'mime' => 'text/csv',
                ...$this->attachment['headers']
            ];
            $mailer->attach($this->attachment['storage_path'], $headers);
        }
        return $mailer;
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
            //
        ];
    }
}
