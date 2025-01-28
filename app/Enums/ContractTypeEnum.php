<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use App\Contracts\Enums\Exceptions;

enum ContractTypeEnum: string implements Exceptions
{
    use Options, Names, _Options;
 
    case MembershipAgreement = 'membership_agreement';

    case GDPRAgreement = 'gdpr_agreement';

    case PartnerAgreement = 'partner_agreement';

    case VirtualMarathonSeriesAgreement = 'vms_agreement';

    case OtherAgreement = 'other_agreement';

    /**
     * Use their defined label instead of the RegexHelper
     * 
     * @return array
     */
    public static function exceptions(): array 
    {
        return [
            'GDPRAgreement' => 'GDPR Agreement'
        ];
    }
}