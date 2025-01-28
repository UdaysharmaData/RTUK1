<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Values;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use App\Contracts\Enums\OrganisationExcludes;
use App\Modules\Setting\Enums\OrganisationCodeEnum;

enum EnquiryActionEnum: string implements OrganisationExcludes
{
    use Options, Names, _Options, Values;

    case RegistrationFailed_EventPlacesExhausted = 'registration_failed_event_places_exhausted';

    case RegistrationFailed_CharityPlacesExhausted = 'registration_failed_charity_places_exhausted';

    case RegistrationFailed_EstimatedEvent = 'registration_failed_estimated_event';

    /**
     * An array of constants not to return for each of the sites belonging to the given organistation
     * 
     * @return array
     */
    public static function organisationExcludes(): array
    {
        return [
            OrganisationCodeEnum::GWActive->value => [EnquiryActionEnum::RegistrationFailed_CharityPlacesExhausted]
        ];
    }
}
