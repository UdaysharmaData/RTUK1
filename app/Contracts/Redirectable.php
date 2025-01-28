<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface Redirectable
{
    /**
     * @return MorphOne
     */
    public function redirect(): MorphOne;

//    /**
//     * @return string
//     */
//    public function url(): string;
}
