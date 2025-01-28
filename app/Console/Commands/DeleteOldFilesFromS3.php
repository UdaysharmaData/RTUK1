<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DeleteOldFilesFromS3 extends Command
{
    protected $signature = 's3:delete-old-files';
    protected $description = 'Delete files from the csvs_path folder in S3 that are older than 2 hr';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $files = Storage::disk('s3')->files(config('app.csvs_path'));
        $disk1 = Storage::disk('s3');

        $cutoffDate = Carbon::now()->subHour(20);

        foreach ($files as $file) {
            $lastModified = Carbon::createFromTimestamp($disk1->lastModified($file));
            if ($lastModified->lessThan($cutoffDate)) {
                $disk1->delete($file);
            }
        }

    }
}
