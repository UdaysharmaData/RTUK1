<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CopyDefaultProfileBackgroundImageAndAvatarToStoragePath extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:copy-profile-background-image-and-default-avatar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy default profile background image to storage path.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $fileName = 'default-background-image.jpg';

            if (File::exists($publicPath = public_path('images/default-background-image.jpg'))) {
                File::copy(
                    $publicPath,
                    config('filesystems.default') == 'local' ? Storage::disk(config('filesystems.default'))->path(config('app.images_path') . '/' . $fileName) : Storage::disk(config('filesystems.default'))->url(config('app.images_path') . '/' . $fileName)
                );
                $this->info('Background Image File copied.');
            } else $this->info("Background Image File not found in path [$publicPath].");

            $fileName = 'default-avatar.png';

            if (File::exists($publicPath = public_path('images/default-avatar.png'))) {
                File::copy(
                    $publicPath,
                    config('filesystems.default') == 'local' ? Storage::disk(config('filesystems.default'))->path(config('app.images_path') . '/' . $fileName) : Storage::disk(config('filesystems.default'))->url(config('app.images_path') . '/' . $fileName)
                );
                $this->info('Avatar File copied.');
            } else $this->info("Avatar File not found in path [$publicPath].");
        } catch (Exception $exception) {
            Log::error($exception);
            $this->error($exception->getMessage());
        }
        return 0;
    }
}
