<?php

namespace App\Services\Analytics\Events;

use App\Contracts\CanHaveManyViews;
use Illuminate\Foundation\Events\Dispatchable;

class AnalyticsViewEvent
{
    use Dispatchable;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public CanHaveManyViews $viewable) {}
}
