<?php

namespace App\Mail\market\resalePlace;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

use App\Mail\MailLayout;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\ResalePlace;


class NewResalePlaceMail extends MailLayout
{
    private Charity $charity;

    private ResalePlace $resalePlace;

    private $places;

    public function __construct(Charity $charity, ResalePlace $resalePlace, $places, $site = null)
    {
        $this->charity = $charity;
        $this->resalePlace = $resalePlace->load('event');
        $this->places = $places;

        parent::__construct($site);
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            to: new Address($this->charity->email ?: $this->charity->charityOwner?->user?->email,
                $this->charity->name),
            subject: "The {$this->resalePlace->event?->name} Event Places Have Been Listed On The Market Place"
        );
    }

    /**
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.market.resale-place.new',
            markdown: 'mails.market.resale-place.new',
            with:[
                'charity' => [
                    'ref' =>  $this->charity->ref,
                    'name' => $this->charity->name
                ],
                'resalePlace' => [
                    'id' => $this->resalePlace->id,
                    'event' => [
                        'name' => $this->resalePlace->event?->name
                    ]
                ]
            ]
        );
    }
}
