<?php

namespace App\Enums;

use App\Traits\Enum\_Options;

enum OrderByDirectionEnum: string
{
    use _Options;

    case Ascending = 'asc';

    case Descending = 'desc';
}
