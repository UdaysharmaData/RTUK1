<?php

namespace App\Enums;

// Get mail class based on role.
// This is used to send emails to newly created accounts.

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum RoleMailEnum: string
{
    use Options, Names, _Options;

    case administrator = 'App\Mail\AdministratorAccountCreatedMail';

    case AccountManager = 'App\Mail\AccountManagerAccountCreatedMail';

    case Charity = 'App\Mail\CharityAccountCreatedMail';

    case Developer = 'App\Mail\DeveloperAccountCreatedMail';

    case CharityUser = 'App\Mail\CharityUserAccountCreatedMail';

    case Partner = 'App\Mail\PartnerAccountCreatedMail';

    case participant = 'App\Mail\ParticipantAccountCreatedMail';

    case EventManager = 'App\Mail\EventManagerAccountCreatedMail';

    case Corporate = 'App\Mail\CorporateAccountCreatedMail';

    case RunthroughData = 'App\Mail\RunthroughDataAccountCreatedMail';

    case ContentManager = 'App\Mail\ContentManagerAccountCreatedMail';
}