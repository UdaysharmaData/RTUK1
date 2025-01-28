<?php

namespace App\Services\Reporting\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use App\Contracts\Enums\OrganisationExcludes;
use App\Modules\Setting\Enums\OrganisationCodeEnum;

enum InvoiceStatisticsTypeEnum: string implements OrganisationExcludes
{
    use Options, Names, _Options;

    case Invoices = 'invoices';

    case ParticipantRegistration = 'participant_registration';

    case MarketResale = 'market_resale';

    case CharityMembership = 'charity_membership';

    case PartnerPackageAssignment = 'partner_package_assignment';

    case EventPlaces = 'event_places';

    case CorporateCredit = 'corporate_credit';

    /**
     * An array of constants not to return for each of the sites belonging to the given organistation
     * 
     * @return array
     */
    public static function organisationExcludes(): array
    {
        return [
            OrganisationCodeEnum::GWActive->value  => [
                InvoiceStatisticsTypeEnum::MarketResale,
                InvoiceStatisticsTypeEnum::CharityMembership,
                InvoiceStatisticsTypeEnum::PartnerPackageAssignment,
                InvoiceStatisticsTypeEnum::EventPlaces,
                InvoiceStatisticsTypeEnum::CorporateCredit
            ]
        ];
    }
}