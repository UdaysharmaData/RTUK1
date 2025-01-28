<?php

namespace App\Services\Analytics\Listeners;

use App\Services\Analytics\AnalyticsCaptureEngine;
use App\Services\Analytics\Enums\InteractionTypeEnum;
use App\Services\Analytics\Events\AnalyticsInteractionEvent;
use App\Services\Analytics\Exceptions\UnsupportedAnalyticsActionException;

class AnalyticsInteractionHandler
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
    public function handle(AnalyticsInteractionEvent $event): void
    {
        $engine = new AnalyticsCaptureEngine($event->interactable);

        $engine->capture(
            'interactions',
            $this->prepareData($event->data)
        );
    }

    protected function prepareData(array $data): array
    {
        $type = request('interaction_type');

        return [
            'type' => isset($type)
                ? InteractionTypeEnum::tryFrom($type)
                : null,
            ...$data
        ];
    }
}
