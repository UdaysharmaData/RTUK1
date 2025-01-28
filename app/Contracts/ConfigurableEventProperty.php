<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Casts\Attribute;

interface ConfigurableEventProperty
{
    /**
     * Update the name based on the site making the request
     *
     * @return Attribute
     */
    function name(): Attribute;
}
