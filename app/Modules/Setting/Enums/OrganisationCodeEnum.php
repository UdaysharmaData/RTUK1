<?php

namespace App\Modules\Setting\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum OrganisationCodeEnum: string
{
    use Options, Names, _Options;

    case GWActive = 'gwactive';

    case SportsMediaAgency = 'sportsmediaagency';
}