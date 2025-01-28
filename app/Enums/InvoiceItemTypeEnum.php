<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use App\Contracts\Enums\OrganisationExcludes;
use App\Modules\Setting\Enums\OrganisationCodeEnum;

enum InvoiceItemTypeEnum: string implements OrganisationExcludes
{
    use Options, Names, _Options;
 
    case ParticipantRegistration = 'participant_registration';

    case MarketResale = 'market_resale';

    case CharityMembership = 'charity_membership';

    case PartnerPackageAssignment = 'partner_package_assignment';

    case EventPlaces = 'event_places';

    case CorporateCredit = 'corporate_credit';

    case ParticipantTransferOldEvent = 'participant_transfer_old_event';

    case ParticipantTransferNewEvent = 'participant_transfer_new_event';

    case ParticipantTransferFee = 'participant_transfer_fee';

    /**
     * An array of constants not to return for each of the sites belonging to the given organistation
     * 
     * @return array
     */
    public static function organisationExcludes(): array
    {
        return [
            OrganisationCodeEnum::GWActive->value => [
                InvoiceItemTypeEnum::MarketResale,
                InvoiceItemTypeEnum::CharityMembership,
                InvoiceItemTypeEnum::PartnerPackageAssignment,
                InvoiceItemTypeEnum::EventPlaces,
                InvoiceItemTypeEnum::CorporateCredit
            ]
        ];
    }
}