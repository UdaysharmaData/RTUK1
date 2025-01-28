<?php

namespace App\Http\Requests;


class CitiesRestoreRequest extends ConfigurableEventPropertyRestoreRequest
{
    /**
     * @var string
     */
    protected string $label = 'cities';
}
