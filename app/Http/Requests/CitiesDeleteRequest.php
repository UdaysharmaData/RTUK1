<?php

namespace App\Http\Requests;

class CitiesDeleteRequest extends ConfigurableEventPropertyDeleteRequest
{
    /**
     * @var string
     */
    protected string $label = 'cities';
}
