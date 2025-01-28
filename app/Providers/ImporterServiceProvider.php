<?php

namespace App\Providers;

use App\Services\DataExchangeService\Contracts\Importable;
use App\Services\DataExchangeService\Exceptions\UnsupportedImporterService;
use App\Services\DataExchangeService\Implementations\ApiImporter;
use Illuminate\Support\ServiceProvider;

class ImporterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Importable::class, function ($app) {
            abort_unless(request()->has('source'), '403', 'No Importer service selected.');

            $source = request()->query('source');

            return match ($source) {
                'api' => new ApiImporter(),
                default => throw new UnsupportedImporterService("The specified importer service [$source] is not currently supported."),
            };
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
