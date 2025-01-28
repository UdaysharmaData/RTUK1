<?php

namespace App\Enums;

use ArchTech\Enums\Values;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use App\Contracts\Enums\OrganisationExcludes;
use App\Modules\Setting\Enums\OrganisationCodeEnum;

enum EntriesListOrderByFieldsEnum: string implements OrganisationExcludes
{
    use Options, Values, _Options;

    case Charity = 'charity';

    case Event = 'event';

    case Status = 'status';

    case CreatedAt = 'created_at';

    /**
     * An array of constants not to return for each of the sites belonging to the given organistation
     * 
     * @return array
     */
    public static function organisationExcludes(): array
    {
        return [
            OrganisationCodeEnum::GWActive->value => [EntriesListOrderByFieldsEnum::Charity]
        ];
    }
}
