<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum ProfileEthnicityEnum: string
{
    use Options, Names, _Options;

    case WhiteBritish = 'white_british';
    
    case WhiteOther = 'white_other';
    
    case AsianOrAsianBritish = 'asian_or_asian_british';

    case PreferNotToSay = 'prefer_not_to_say';

    case MixedOrMultipleEthnicGroups = 'mixed_or_multiple_ethnic_groups';

    case BlackOrBlackBritish = 'black_or_black_british';

    case OtherEthnicGroup = 'other_ethnic_group';
}