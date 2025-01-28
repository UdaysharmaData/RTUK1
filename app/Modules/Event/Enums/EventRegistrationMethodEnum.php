<?php

namespace App\Modules\Event\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use App\Traits\Enum\_Options;

enum EventRegistrationMethodEnum: string
{
    use Options, Names, _Options;

    case WebsiteRegistrationMethod = 'website_registration_method';

    case PortalRegistrationMethod = 'portal_registration_method';
}