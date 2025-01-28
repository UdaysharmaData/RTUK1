<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum PromotionalPageTypeEnum: string
{
    use Options, Names, _Options;

    case PromotionalPage1 = 'promotional_page_1';

    case PromotionalPage2 = 'promotional_page_2';
}