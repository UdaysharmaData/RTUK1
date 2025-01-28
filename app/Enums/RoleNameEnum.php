<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use ArchTech\Enums\Values;

enum RoleNameEnum: string
{
    use Options, Values, Names, _Options;

    case Administrator = 'administrator';

    case AccountManager = 'account_manager';

    case Charity = 'charity';

    case Developer = 'developer';

    case CharityUser = 'charity_user';

    case Partner = 'partner';

    case Participant = 'participant';

    case EventManager = 'event_manager';

    case Corporate = 'corporate';

    case RunthroughData = 'runthrough_data';

    case ContentManager = 'content_manager';
}
