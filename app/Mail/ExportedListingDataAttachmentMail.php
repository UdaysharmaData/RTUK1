<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExportedListingDataAttachmentMail extends MailLayout
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(protected string $s3PathLink,protected string $name, $site = null)
    {
        parent::__construct($site);
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope();
    }

    /**
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.export',
            markdown: 'mails.export',
            with: [
                'name' => $this->name,
                's3PathLink'=>$this->s3PathLink
            ]
        );
    }
}
