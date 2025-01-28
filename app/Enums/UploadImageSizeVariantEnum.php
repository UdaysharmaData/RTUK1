<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum UploadImageSizeVariantEnum: string 
{
    use Options, Names, _Options;

    case Mobile = 'mobile';
    
    case Tablet = 'tablet';

    case Desktop = 'desktop';

    case Card = 'card';
}