<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use App\Contracts\Enums\Exceptions;

enum SocialPlatformEnum: string implements Exceptions
{
    use Options, Names, _Options;

    case Google = 'google';

    case Facebook = 'facebook';

    case Twitter = 'twitter';

    case Instagram = 'instagram';

    case Tiktok = 'tiktok';

    case LinkedIn = 'linkedin';

    case YouTube = 'youtube';

    case Vimeo = 'vimeo';

    case GitHub = 'github';

    /**
     * Use their defined label instead of the RegexHelper
     *
     * @return array
     */
    public static function exceptions(): array
    {
        return [
            'LinkedIn' => 'LinkedIn',
            'YouTube' => 'YouTube'
        ];
    }
}
