<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum CallNoteStatusEnum: string
{
    use Options, Names, _Options;

    case MadeContact = 'made_contact';

    case NoAnswer = 'no_answer';
}