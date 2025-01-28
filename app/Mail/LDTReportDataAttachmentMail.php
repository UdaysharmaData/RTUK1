<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
class LDTReportDataAttachmentMail extends MailLayout
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(protected string $s3PathLink, $site = null)
    {
        parent::__construct($site);
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        

        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            to: $this->mailHelper->developerMembers(),
            subject: "LDT Sync Report - " . Carbon::now()->format("Y-m-d"),
        );
    }

    /**
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.reports',
            markdown: 'mails.reports',
            with: [
                's3PathLink'=>$this->s3PathLink
            ]
        );
    }
}
