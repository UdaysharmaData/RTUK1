<?php

namespace App\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use App\Contracts\Enums\Exceptions;

enum UploadTypeEnum: string implements Exceptions
{
    use Options, Names, _Options;

    case Image = 'image';

    case Video = 'video';

    case PDF = 'pdf';

    case CSV = 'csv';

    case Audio = 'audio';

    /**
     * Use their defined label instead of the RegexHelper
     * // TODO: Get a better name for this method and its interface.
     * 
     * @return array
     */
    public static function exceptions(): array 
    {
        return [
            'PDF' => 'PDF',
            'CSV' => 'CSV',
        ];
    }
}
