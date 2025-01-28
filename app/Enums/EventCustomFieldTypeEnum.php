<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum EventCustomFieldTypeEnum: string
{
    use Options, Names, _Options;

    case Text = 'text';

    case TextArea = 'textarea';

    case Select = 'select';

    case Radio = 'radio';

    case Checkbox = 'checkbox';
}