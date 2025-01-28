<?php

namespace App\Console\Commands;

use App\Enums\UploadTypeEnum;
use App\Models\Upload;
use App\Models\Uploadable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class UpdateUploadsDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:update-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the uploads data to the new structure.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (Schema::hasColumn('uploads', 'uploadable_id')) {
            Upload::query()->chunk(500, function ($uploads) {
                foreach ($uploads as $upload) {
                    if ($upload->type == UploadTypeEnum::Image) {
                        if (Uploadable::where('upload_id', $upload->id)->exists()) {
                            continue;
                        } else {
                            $storage = Storage::disk(config('filesystems.default'));
                            $url = $upload->url;
                            $folderPath = (explode('.', $url))[0];
                            $newUrl = "$folderPath/" . basename($url);

                            if ($storage->exists($url)) {
                                $storage->exists($folderPath) or $storage->makeDirectory($folderPath);
                                $storage->move($url, $newUrl);
                            }

                            $upload->update(['url' => $newUrl]);
                        }
                    }

                    Uploadable::updateOrcreate([
                        'use_as' => $upload->use_as,
                        'upload_id' => $upload->id,
                        'uploadable_id' => $upload->uploadable_id,
                        'uploadable_type' => $upload->uploadable_type
                    ]);
                }
            });

            Schema::dropColumns('uploads', ['uploadable_id', 'uploadable_type', 'use_as']);
        } else {
            $this->info('The uploads table has already been updated.');
        }
        return Command::SUCCESS;
    }
}
