<?php

namespace App\Http\Controllers;

use App\Mail\charity\membership\MembershipExpiredMail;
use App\Mail\charity\places\CharityPlacesExhaustedMail;
use App\Mail\enquiry\external\ldt\FailedToOfferPlacesMail;
use App\Mail\event\AttemptRegistrationOnEstimatedEvent;
use App\Mail\event\EventArchivedMail;
use App\Mail\event\TotalPlacesExhaustedMail;
use App\Mail\participant\AttemptRegisteredDeletedAccountMail;
use App\Mail\participant\entry\ParticipantUncompletedRegistration;
use App\Mail\user\UserAccountCreatedMail;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Participant\Models\Participant;
use App\Modules\User\Models\User;
use App\Traits\AdministratorEmails;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\Mail;

class TestController extends Controller
{
    use AdministratorEmails;

    private $user;

    public function __construct()
    {
        parent::__construct();

        $this->user = User::first();

    }


    public function totalPlacesExhaustedMail(): string
    {
        $eec = EventEventCategory::first();
        static::sendEmails(new TotalPlacesExhaustedMail($eec, $this->user));
        return 'success';
    }

    public function attemptedRegisteredDeletedAccountMail(): string
    {
        $eec = EventEventCategory::first();
        static::sendEmails(new AttemptRegisteredDeletedAccountMail($this->user, $eec));
        return 'success';
    }

    public function charityPlacesExhaustedMail()
    {
        $eec = EventEventCategory::first();
        $user = $this->user;
        $charity = Charity::first();
        Mail::site()->send(new CharityPlacesExhaustedMail($eec, $user, $charity));
        return 'success';
    }

    public function participantRegistrationMail(): string
    {
        return 'success';
    }

    public function uncompletedRegistrationMail(): string
    {
        Mail::site()->send(new ParticipantUncompletedRegistration(Participant::whereDoesntHave('charity')->first()));
        return 'success';
    }

    public function accountCreatedMail(): string
    {
        Mail::site()->send(new UserAccountCreatedMail($this->user, 'password'));
        return 'success';
    }

    public function eventArchivedMail()
    {
        $event = Event::first();
        static::sendEmails(new EventArchivedMail($event, $event));
        return 'success';
    }

    public function membershipExpiredMail(): string
    {
        Mail::site()->send(new MembershipExpiredMail(Charity::first()));
        return 'success';
    }

    public function attemptRegistrationMail()
    {
        static::sendEmails(new AttemptRegistrationOnEstimatedEvent(Event::first(), $this->user));
        return 'success';
    }

    public function failedToOfferPlacesToLDT(): string
    {
        static::sendEmails(new FailedToOfferPlacesMail(25));
        return 'success';
    }

    public function invoiceView()
    {
        $data = $this->invoiceData();
        return view('pdf.invoice', $data);
    }

    public function invoicePdfView() 
    {
        $data = $this->invoiceData();
        $pdf = Pdf::loadView('pdf.invoice', $data);
        return $pdf->stream();
    }

    private function invoiceData() {
        $title = 'Invoice for John Doe';
        $logo = 'https://runthrough.runthroughhub.com/assets/images/logo.png';
        $issueDate = 'October 04, 2022';
        $dueDate = 'November 12, 2022';
        $name = 'John Doe';
        $description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.';
    
        //    Information content
        $headerInfo = [
            ['value' => 'ID'],
            ['value' => 'Status'],
            ['value' => 'Issue Date'],
            ['value' => 'Due Date'],
            ['value' => 'Billing Address'],
        ];
    
        $bodyInfo = [
            [
                ['value' => '<span class="break_word">20240118425</span>'],
                ['value' => '<span class="pill danger">Unpaid</span>'],
                ['value' => $issueDate],
                ['value' => $dueDate],
                ['value' => '<div class="item">
                            <div class="item__description">PO Box 16122 Collins Street WestVictoria 8007 Australia</div>
                            <div class="item__description">Phone: <a href="tel:+123 456 7890" data-site="runthrough">+123 456 7890</a></div>
                            <div class="item__description">Email: <a href="mailto:email@address.com" data-site="runthrough">email@address.com</a></div>
                            <div class="item__description">Website: <a href="https://website.com" data-site="runthrough">https://website.com</a></div>
                        </div>'],
            ]
        ];
    
        //    Summary Content
        $headerSummary = [
            ['value' => '#'],
            ['value' => 'Item'],
            ['value' => 'Quantity', 'className' => 'text__center'],
            ['value' => 'Price', 'className' => 'text__center'],
            ['value' => 'Total', 'className' => 'text__center']
        ];
    
        $bodySummary = [
            [
                ['value' => '1', 'className' => 'text__bold'],
                ['value' => '<div class="item">
                                <div class="item__title">Web Design</div>
                                <div class="item__description">Lorem Ipsum is simply dummy text of the printing and typesetting industry.</div>
                            </div>'],
                ['value' => '5', 'className' => 'text__center'],
                ['value' => '£120.00', 'className' => 'text__center'],
                ['value' => '£2,880.00', 'className' => 'text__center text__bold']
            ],
            [
                ['value' => '2', 'className' => 'text__bold'],
                ['value' => '<div class="item">
                                <div class="item__title">Frontend Development</div>
                                <div class="item__description">Lorem Ipsum is simply dummy text of the printing and typesetting industry.</div>
                            </div>'],
                ['value' => '3', 'className' => 'text__center'],
                ['value' => '£150.00', 'className' => 'text__center'],
                ['value' => '£3,580.00', 'className' => 'text__center text__bold']
            ],
            [
                ['value' => '3', 'className' => 'text__bold'],
                ['value' => '<div class="item">
                                <div class="item__title">Backend Development</div>
                                <div class="item__description">Lorem Ipsum is simply dummy text of the printing and typesetting industry.</div>
                            </div>'],
                ['value' => '4', 'className' => 'text__center'],
                ['value' => '£155.00', 'className' => 'text__center'],
                ['value' => '£3,880.00', 'className' => 'text__center text__bold']
            ],
        ];
    
        //    Details content
        $headerDetails = [
            ['value' => 'Bank'],
            ['value' => 'Account Number'],
            ['value' => 'Sort Code'],
            ['value' => 'Subtotal', 'className' => 'text__center'],
            ['value' => 'Discount', 'className' => 'text__center'],
            ['value' => 'Total', 'className' => 'text__center']
        ];
    
        $bodyDetails = [
            [
                ['value' => 'Lloyds Banking Group', 'className' => 'text__bold'],
                ['value' => '20412131368', 'className' => 'text__bold'],
                ['value' => '30–97–86', 'className' => 'text__bold'],
                ['value' => '£4,597.50', 'className' => 'text__center'],
                ['value' => '10%', 'className' => 'text__center '],
                ['value' => '£4,137.75', 'className' => 'text__center text__bold']
            ]
        ];
    
        $note = 'Payments are due 14 days from the receipt of this invoice and payable to <strong data-site="runthrough">RunThrough (Company No: 08343864), 33 Wood St, Barnet EN5 4BE</strong>.';
        
        return [
            'title' => $title,
            'logo' => $logo,
            'name' => $name,
            'description' => $description,
            'headerInfo' => $headerInfo,
            'bodyInfo' => $bodyInfo,
            'headerDetails' => $headerDetails,
            'bodyDetails' => $bodyDetails,
            'headerSummary' => $headerSummary,
            'bodySummary' => $bodySummary,
            'issueDate' => $issueDate,
            'dueDate' => $dueDate,
            'note' => $note
        ];
    }

}
