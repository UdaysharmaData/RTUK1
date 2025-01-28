<?php

namespace App\Services\Analytics\Listeners;

use App\Services\Analytics\AnalyticsCaptureEngine;
use App\Services\Analytics\Events\AnalyticsViewEvent;
use App\Services\Analytics\Exceptions\UnsupportedAnalyticsActionException;

class AnalyticsViewHandler
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() {}


    /**
     * @throws UnsupportedAnalyticsActionException
     */
    public function handle(AnalyticsViewEvent $event): void
    {
        $engine = new AnalyticsCaptureEngine($event->viewable);

        $engine->capture('views');
    }
}
