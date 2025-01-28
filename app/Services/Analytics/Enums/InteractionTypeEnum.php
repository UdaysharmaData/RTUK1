<?php

namespace App\Services\Analytics\Enums;

use App\Traits\Enum\_Options;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

enum InteractionTypeEnum: string
{
    use Options, Values, _Options;

    case ReadMoreAbout = 'read_more_about';

    case ClickToParticipate = 'click_to_participant';

    case ReadFAQs = 'read_faqs';
}
