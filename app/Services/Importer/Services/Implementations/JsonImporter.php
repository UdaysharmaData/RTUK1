<?php

namespace App\Services\Importer\Services\Implementations;

use App\Services\Importer\Conditions\ExampleCondition;
use App\Services\Importer\Services\FileImporter;
use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\DecodingError;
use JsonMachine\JsonDecoder\ErrorWrappingDecoder;
use JsonMachine\JsonDecoder\ExtJsonDecoder;

final class JsonImporter extends FileImporter
{
    /**
     * @var string
     */
    protected string $filePath;

    /**
     * @param string $filePath
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    public function import(): void
    {
        $checkPoint = $this->getLastCheckPoint();

        foreach ($this->getItemsFromFileReader() as $key => $item) {
            if ($key instanceof DecodingError || $item instanceof DecodingError) {
                continue;
            }

            if ($this->stopProcessingFlag) {
                echo "You stopped the command";

                return;
            }

            if (is_int($checkPoint) && $checkPoint >= $key) {
                echo "Skipping already processed item #$key".PHP_EOL;

                continue;
            }

            if ($this->passesChecks($item)) {
                $this->saveToDatabase($item);
            }
            $this->setCheckPoint($key);
        }

        $this->clearCheckPoint();
    }

    /**
     * @return Items
     * @throws InvalidArgumentException
     */
    protected function getItemsFromFileReader(): Items
    {
        return Items::fromFile($this->getFileFromStorage($this->filePath), [
            'decoder' => new ErrorWrappingDecoder(new ExtJsonDecoder(assoc: true))
        ]);
    }
}
