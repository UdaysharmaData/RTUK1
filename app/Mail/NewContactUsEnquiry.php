<?php

namespace App\Mail;

use App\Models\ClientEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewContactUsEnquiry extends MailLayout
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public ClientEnquiry $enquiry, $site = null)
    {
        parent::__construct($site);
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->enquiry->email, $this->enquiry->full_name),
            subject: 'New Enquiry - '. $this->reformatType($this->enquiry->enquiry_type),
        );
    }

    /**
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.enquiries.contact-us',
            markdown: 'mails.enquiries.contact-us',
            with: [
                'name' => 'Admin',
                'intro' => 'You have a new enquiry.',
                'text' => $this->enquiry->message
            ]
        );
    }

    /**
     * @param mixed $type
     * @return string
     */
    private function reformatType(mixed $type): string
    {
        return ucwords(str_replace('_', ' ', $type));
    }
}
