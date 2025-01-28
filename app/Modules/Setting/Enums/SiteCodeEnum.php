<?php

namespace App\Modules\Setting\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;
use App\Contracts\Enums\Exceptions;
use App\Modules\Setting\Models\Site;

enum SiteCodeEnum: string implements Exceptions
{
    use Options, Names, _Options;

    case SportForCharity = 'sportforcharity';

    case RunForCharity = 'runforcharity';
    
    case CycleForCharity = 'cycleforcharity';
 
    case XeroForCharity = 'xeroforcharity';

    case RunThroughHub = 'runthroughhub';

    case RunThrough = 'runthrough';

    case VirtualMarathonSeries = 'virtualmarathonseries';

    case RunningGrandPrix = 'runninggrandprix';

    case Leicestershire10K = 'leicestershire10k';

    /**
     * Use their defined label instead of the RegexHelper
     * // TODO: Get a better name for this method and its interface.
     * 
     * @return array
     */
    public static function exceptions(): array 
    {
        return [
            'RunThroughHub' => 'RunThroughHub',
            'RunThrough' => 'RunThrough',
        ];
    }
}