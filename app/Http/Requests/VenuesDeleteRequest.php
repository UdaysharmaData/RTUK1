<?php

namespace App\Http\Requests;

class VenuesDeleteRequest extends ConfigurableEventPropertyDeleteRequest
{
    /**
     * @var string
     */
    protected string $label = 'venues';
}
