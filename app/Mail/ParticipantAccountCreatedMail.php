<?php

namespace App\Mail;

use App\Http\Helpers\MailHelper;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Participant\Models\Participant;
use App\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParticipantAccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    private Participant $participant;

    private EventEventCategory $eec;

    public MailHelper $mailInfo;

    private string $password;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Participant $participant, EventEventCategory $eec, $password)
    {
        $this->participant = $participant;
        $this->eec = $eec;
        $this->password = $password;
        $this->mailInfo = new MailHelper();
    }

    /**
     * Get the message envelope.
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->mailInfo->genericAddress('charities'),
            to: $this->participant->user->email,
            subject: $this->eec->event->name.' Registration',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'mails.account.participant-registration',
            with: [
                'user' => (object)[
                    'first_name' => $this->participant->user->first_name,
                    'email' => $this->participant->user->email,
                    'password' => $this->password,
                    ''
                ]
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
