<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum SettingCustomFieldKeyEnum: string
{
    use Options, Names, _Options;

    case ParticipantTransferFee = 'participant_transfer_fee';

    case ClassicMembershipDefaultPlaces = 'classic_membership_default_places';

    case PremiumMembershipDefaultPlaces = 'premium_membership_default_places';

    case TwoYearMembershipDefaultPlaces = 'two_year_membership_default_places';

    case PartnerMembershipDefaultPlaces = 'partner_membership_default_places';

    case ClassicRenewal = 'classic_renewal';

    case NewClassicRenewal = 'new_classic_renewal';

    case PremiumRenewal = 'premium_renewal';

    case NewPremiumRenewal = 'new_premium_renewal';

    case TwoYearRenewal = 'two_year_renewal';

    case NewTwoYearRenewal = 'new_two_year_renewal';

    case PartnerRenewal = 'partner_renewal';

    case NewPartnerRenewal = 'new_partner_renewal';
}