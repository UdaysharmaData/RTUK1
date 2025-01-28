<?php

namespace App\Contracts\Uploadables;

use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface CanHaveUploadableResource
{
    /**
     * @return HasOneThrough
     */
    public function upload() :HasOneThrough;
    
    /**
     * uploadable
     *
     * @return MorphOne
     */
    public function uploadable(): MorphOne;
}
