<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use App\Contracts\Enums\OrganisationExcludes;
use App\Modules\Setting\Enums\OrganisationCodeEnum;

enum PredefinedPartnerChannelEnum: string implements OrganisationExcludes
{
    use Options, Names, _Options;

    case Bespoke = 'lets-do-this-bespoke';

    case Incremental = 'lets-do-this-incremental';

    case NonIncremental = 'lets-do-this-non-incremental';

    case Unrecognized = 'lets-do-this-unrecognized';

    case Pending = 'lets-do-this-pending';

    /**
     * An array of constants not to return for each of the sites belonging to the given organistation
     * 
     * @return array
     */
    public static function organisationExcludes(): array
    {
        return [
            OrganisationCodeEnum::GWActive->value => [PredefinedPartnerChannelEnum::Incremental, PredefinedPartnerChannelEnum::NonIncremental, PredefinedPartnerChannelEnum::Unrecognized, PredefinedPartnerChannelEnum::Pending],
        ];
    }
}
