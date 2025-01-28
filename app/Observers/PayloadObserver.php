<?php

namespace App\Observers;

use App\Jobs\ProcessPayload;
use App\Models\Payload;

class PayloadObserver
{
    /**
     * Handle the Payload "created" event.
     *
     * @param  \App\Models\Payload  $payload
     * @return void
     */
    public function created(Payload $payload)
    {
        ProcessPayload::dispatch($payload);
    }
}
