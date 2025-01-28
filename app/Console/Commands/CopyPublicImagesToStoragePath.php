<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CopyPublicImagesToStoragePath extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:copy-public-images-to-storage-path';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy the content of app/public/images directory to the public directory under storage.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            File::copyDirectory(public_path('images'), Storage::disk(config('filesystems.default'))->path(config('app.images_path')));
            echo 'Done';
        } catch (Exception $exception) {
            Log::error($exception);
            $this->error($exception->getMessage());
        }

        return 0;
    }
}
