<?php

namespace App\Services\Importer\Services;

use App\Services\Importer\Conditions\ExampleCondition;
use App\Services\Importer\Contracts\ImportableInterface;
use App\Services\Importer\Contracts\ImportConditionInterface;
use Illuminate\Support\Facades\Cache;


abstract class FileImporter implements ImportableInterface
{
    /**
     * @var ImportConditionInterface[]
     */
    private array $conditions = [];
    /**
     * @var bool
     */
    protected bool $stopProcessingFlag = false;

    /**
     * @param string $path
     * @return string
     */
    public function getFileFromStorage(string $path): string
    {
        return storage_path("app/data/{$path}");
    }

    /**
     * @param ImportConditionInterface $importCondition
     * @return void
     */
    public function addCondition(ImportConditionInterface $importCondition): void
    {
        $this->conditions[] = $importCondition;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function passesChecks(array $data): bool
    {
        $this->addCondition( new ExampleCondition);

        foreach ($this->conditions as $condition) {
            if(! $condition->isPassed($data)) {
                echo "Discarding data".PHP_EOL;

                return false;
            }
        }

        echo "Processing data".PHP_EOL;

        return true;
    }

    /**
     * @return void
     */
    public function stopProcessing(): void
    {
        $this->stopProcessingFlag = true;
    }

    /**
     * @param array $data
     * @return void
     */
    protected function saveToDatabase(array $data): void
    {
        // write data to database;
    }

    /**
     * @return string
     */
    private function getCheckpointCacheKey(): string {
        return sprintf('import-file-checkpoint-%s', basename($this->filePath));
    }

    /**
     * @return int|null
     */
    protected function getLastCheckPoint(): ?int
    {
        return Cache::get($this->getCheckpointCacheKey());
    }

    /**
     * @param int $key
     * @return void
     */
    protected function setCheckPoint(int $key)
    {
        $lastCheckPoint = $this->getLastCheckPoint();

        if (
            is_null($lastCheckPoint)
            || (is_int($lastCheckPoint) && $key > $lastCheckPoint)
        ) {
            Cache::put($this->getCheckpointCacheKey(), $key);
        }
    }

    /**
     * @return void
     */
    protected function clearCheckPoint()
    {
        Cache::forget($this->getCheckpointCacheKey());
    }

    /**
     * @return void
     */
    abstract public function import(): void;
}
