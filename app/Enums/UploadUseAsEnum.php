<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use App\Contracts\Enums\Exceptions;

enum UploadUseAsEnum: string implements Exceptions
{
    use Options, Names, _Options;

    case Logo = 'logo';

    case Image = 'image';

    case Images = 'images';

    case Gallery = 'gallery';

    case RouteInfo = 'route_info';

    case WhatIsIncluded = 'what_is_included';

    case PDF = 'pdf';

    case Avatar = 'avatar';

    case ProfileBackgroundImage = 'profile_background_image';

    /**
     * Use their defined label instead of the RegexHelper
     * // TODO: Get a better name for this method and its interface.
     * 
     * @return array
     */
    public static function exceptions(): array 
    {
        return [
            'PDF' => 'PDF'
        ];
    }
}
