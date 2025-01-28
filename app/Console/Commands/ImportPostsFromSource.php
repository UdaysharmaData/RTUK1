<?php

namespace App\Console\Commands;

use App\Services\DataExchangeService\Factories\ImporterFactory;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use function PHPUnit\Framework\throwException;

class ImportPostsFromSource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:data {source=api}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calls an action to import data from specified source.';

    /**
     * Execute the console command.
     *
     * @param ImporterFactory $importerFactory
     * @return int
     */
    public function handle(ImporterFactory $importerFactory): int
    {
        try {
            $importerFactory->configureSource($this->argument('source'))
                ->fetch()
                ->persist();
        } catch (Exception $exception) {
            $message = $exception->getMessage();

            Log::error($message);
            $this->error($message);

            throwException($exception);
        }

        return 0;
    }
}
