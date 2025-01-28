<?php

namespace App\Services\Importer\Console\Commands;

use App\Services\Importer\Contracts\ImportableInterface;
use App\Services\Importer\Factories\ImportFactory;
use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;

class ImportDataFromFile extends Command implements SignalableCommandInterface
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:data {file_path}';

    /**
     * @var ImportableInterface
     */
    private ImportableInterface $importer;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param ImportFactory $importFactory
     * @return int
     * @throws Exception
     */
    public function handle(ImportFactory $importFactory): int
    {
        $this->importer = $importFactory->initializeImporter($this->argument('file_path'));

        $this->importer->import();

        return 0;
    }

    /**
     * @return array
     */
    public function getSubscribedSignals(): array
    {
        return [SIGTERM, SIGINT];
    }

    /**
     * @param int $signal
     * @return void
     */
    public function handleSignal(int $signal): void
    {
        if (in_array($signal, [SIGTERM, SIGINT])) {
            $this->importer->stopProcessing();

            return;
        }
    }
}
