<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum AudienceSourceEnum: string
{
    use Options, Names, _Options;

    case Emails = 'emails';

    case MailingList = 'mailing list';
//
//    case Participants = 'participants';
//
//    case Charities = 'charities';
}
