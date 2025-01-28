<?php

namespace App\Modules\Event\Requests;

use App\Http\Requests\ConfigurableEventPropertyDeleteRequest;

class SponsorsDeleteRequest extends ConfigurableEventPropertyDeleteRequest
{
    /**
     * @var string
     */
    protected string $label = 'sponsors';
}
