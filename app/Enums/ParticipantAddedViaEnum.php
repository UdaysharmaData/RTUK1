<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum ParticipantAddedViaEnum: string
{
    use Options, Names, _Options;

    case PartnerEvents = 'partner_events';

    case BookEvents = 'book_events';

    case RegistrationPage = 'registration_page';

    case ExternalEnquiryOffer = 'external_enquiry_offer';

    case TeamInvitation = 'team_invitation';

    case Website = 'website';

    case Transfer = 'transfer';
}