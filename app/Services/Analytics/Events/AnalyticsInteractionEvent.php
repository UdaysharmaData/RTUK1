<?php

namespace App\Services\Analytics\Events;

use App\Contracts\CanHaveManyInteractions;
use Illuminate\Foundation\Events\Dispatchable;

class AnalyticsInteractionEvent
{
    use Dispatchable;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public CanHaveManyInteractions $interactable,
        public array $data = []
    ) {}
}
