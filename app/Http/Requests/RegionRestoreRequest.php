<?php

namespace App\Http\Requests;

class RegionRestoreRequest extends ConfigurableEventPropertyRestoreRequest
{
    /**
     * @var string
     */
    protected string $label = 'regions';
}
