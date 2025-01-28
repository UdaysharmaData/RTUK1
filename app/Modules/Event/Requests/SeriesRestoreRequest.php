<?php

namespace App\Modules\Event\Requests;

use App\Http\Requests\ConfigurableEventPropertyRestoreRequest;

class SeriesRestoreRequest extends ConfigurableEventPropertyRestoreRequest
{
    /**
     * @var string
     */
    protected string $label = 'series';
}
