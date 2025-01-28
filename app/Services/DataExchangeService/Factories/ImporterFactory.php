<?php

namespace App\Services\DataExchangeService\Factories;

use App\Services\DataExchangeService\Exceptions\UnsupportedImporterService;
use App\Services\DataExchangeService\Implementations\ApiImporter;

class ImporterFactory
{
    /**
     * @param string $source
     * @return ApiImporter
     * @throws UnsupportedImporterService
     */
    public function configureSource(string $source = 'api'): ApiImporter
    {
       return match ($source) {
            'api' => new ApiImporter,
            default => throw new UnsupportedImporterService("The specified importer service [$source] is not currently supported."),
        };
    }
}
