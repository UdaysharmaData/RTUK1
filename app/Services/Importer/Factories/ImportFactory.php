<?php

namespace App\Services\Importer\Factories;

use App\Services\Importer\Contracts\ImportableInterface;
use App\Services\Importer\Services\Implementations\CsvImporter;
use App\Services\Importer\Services\Implementations\JsonImporter;
use Exception;

final class ImportFactory
{
    /**
     * @param string $filePath
     * @return string
     */
    private function setFileExtension(string $filePath): string
    {
        return pathinfo($filePath, PATHINFO_EXTENSION);
    }

    /**
     * @param string $filePath
     * @return ImportableInterface
     * @throws Exception
     */
    public function initializeImporter(string $filePath) : ImportableInterface
    {
        $extension = $this->setFileExtension($filePath);

        if ($extension === 'json') {
            return new JsonImporter($filePath);
        } elseif ($extension === 'csv') {
            return new CsvImporter($filePath);
        } else throw new Exception("File type with .$extension extension is not currently supported.");
    }
}
