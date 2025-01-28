<?php

namespace App\Modules\Event\Requests;
use App\Http\Requests\ConfigurableEventPropertyDeleteRequest;

class SeriesDeleteRequest extends ConfigurableEventPropertyDeleteRequest
{
    /**
     * @var string
     */
    protected string $label = 'series';
}
