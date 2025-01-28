<?php

namespace App\Events;

use App\Models\Invoice;
use App\Modules\User\Models\User;
use App\Modules\Setting\Models\Site;
use Illuminate\Queue\SerializesModels;
use App\Modules\Enquiry\Models\Enquiry;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\Participant\Models\ParticipantExtra;

class ParticipantNewRegistrationsEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User|string $user;

    public array $extraData;

    public Invoice|Transaction|null $invoiceOrRefundTransaction;

    public Site|null $site;

    public ExternalEnquiry|Enquiry|null $externalEnquiry;

    public ParticipantExtra|null $participantExtra;

    /**
     * Create a new event instance.
     * 
     * @param  User|string                   $user
     * @param  array                         $extraData
     * @param  Invoice|Transaction|null                      $invoiceOrRefundTransaction
     * @param Site|null                      $site
     * @param ExternalEnquiry|Enquiry|null   $externalEnquiry
     * @param ParticipantExtra|null          $participantExtra
     *
     * @return void
     */
    public function __construct(User|string $user, array $extraData, Invoice|Transaction|null $invoiceOrRefundTransaction = null, ?Site $site = null, ExternalEnquiry|Enquiry|null $externalEnquiry = null, ?ParticipantExtra $participantExtra = null)
    {
        $this->user = $user;
        $this->invoiceOrRefundTransaction = $invoiceOrRefundTransaction;
        $this->extraData = $extraData;
        $this->site = $site;
        $this->externalEnquiry = $externalEnquiry;
        $this->participantExtra = $participantExtra;
    }
}
