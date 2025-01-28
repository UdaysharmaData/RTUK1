<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Modules\Charity\Models\Charity;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendMembershipRenewalInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $charity;
    public $invoice;
    public $numOfReminders;

    /**
     * Create a new message instance.
     * @param string subject
     * @param Charity $charity
     * @param Invoice $invoice
     * @param null|int $numOfReminders
     * @return void
     */
    public function __construct(string $subject, Charity $charity, Invoice $invoice, ?int $numOfReminders = null)
    {
        $this->subject = $subject;
        $this->charity = $charity;
        $this->invoice = $invoice;
        $this->numOfReminders = $numOfReminders;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if (isset($this->numOfReminders) && isset($this->charity->charityManager) && $this->numOfReminders >= 3) {
            $this->cc($this->charity->charityManager->user->email);
        }

        return $this->from($address = config('mail.from.address'), $name = config('mail.from.name'))
            ->view('mails.membership-renewal')
            ->text('mails.plain.membership-renewal')
            ->attach(Storage::disk(config('filesystems.default'))->path($this->invoice->upload?->url), [
                'as' => 'invoice.pdf',
                'mime' => 'application/pdf'
            ]);
    }
}
