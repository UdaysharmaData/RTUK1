<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use App\Contracts\Enums\OrganisationExcludes;
use App\Modules\Setting\Enums\OrganisationCodeEnum;

enum ParticipantWaiverEnum: string implements OrganisationExcludes
{
    use Options, Names, _Options;

    case Charity = 'charity';

    case Corporate = 'corporate';

    case Partner = 'partner';

    /**
     * An array of constants not to return for each of the sites belonging to the given organistation
     * 
     * @return array
     */
    public static function organisationExcludes(): array
    {
        return [
            OrganisationCodeEnum::GWActive->value => [ParticipantWaiverEnum::Charity, ParticipantWaiverEnum::Corporate]
        ];
    }
}