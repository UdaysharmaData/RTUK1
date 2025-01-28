<?php

namespace App\Http\Requests;

class RegionDeleteRequest extends ConfigurableEventPropertyDeleteRequest
{
    /**
     * @var string
     */
    protected string $label = 'regions';
}
