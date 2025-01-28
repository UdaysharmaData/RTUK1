<?php

namespace App\Services\Analytics;

use App\Contracts\CanHaveAnalyticsMetadata;
use App\Services\Analytics\Contracts\AnalyzableInterface;
use App\Services\Analytics\Exceptions\UnsupportedAnalyticsActionException;
use App\Services\ApiClient\ApiClientSettings;

class AnalyticsCaptureEngine
{
    public function __construct(protected AnalyzableInterface $analyzable) {}

    /**
     * @param string $action
     * @param array $data
     * @return CanHaveAnalyticsMetadata|null
     * @throws UnsupportedAnalyticsActionException
     */
    public function capture(string $action, array $data = []): CanHaveAnalyticsMetadata|null
    {
        if (! method_exists($this->analyzable, $action)) {
            $className = get_class($this->analyzable);

            throw new UnsupportedAnalyticsActionException(
                "This action [$action] is not supported on the [$className] class."
            );
        }

        if ($this->actionNotAlreadyCaptured($action)) {
            $analyzableData = $this->analyzable->{$action}()->create($data);
            $this->analyzable->totalCount()->firstOrCreate()->increment('total');

            return $analyzableData;
        }

        return null;
    }

    /**
     * @param string $action
     * @return bool
     */
    protected function actionNotAlreadyCaptured(string $action): bool
    {
        return $this->analyzable->{$action}()
            ->whereRelation('metadata', 'identifier', '=', ApiClientSettings::requestIdentifierToken())
            ->doesntExist();
    }
}
