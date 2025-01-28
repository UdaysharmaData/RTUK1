<?php

namespace App\Http\Requests;


class VenuesRestoreRequest extends ConfigurableEventPropertyRestoreRequest
{
    /**
     * @var string
     */
    protected string $label = 'venues';
}
