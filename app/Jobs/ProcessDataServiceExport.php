<?php

namespace App\Jobs;

use App\Modules\User\Models\User;
use App\Notifications\ExportedListingDataAttachmentReadyNotification;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\ExportManager\FileExporterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request as HttpRequest;

class ProcessDataServiceExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public int $timeout = 500;

    public function __construct(
        protected FileExporterService $exporterService,
        protected $request,
        protected Authenticatable $user,
        protected  $site
    ) {
        $this->request = $request->all();
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws ExportableDataMissingException
     */
    public function handle(): void
    {
       
        $attachment = $this->exporterService->download($this->request, true,$this->site,$this->user);

        if (is_array($attachment) && isset($attachment['storage_path']) && isset($attachment['headers'])) {
            $this->user->notify(new ExportedListingDataAttachmentReadyNotification($attachment, $this->user,$this->site));
            if (config('filesystems.default') !== 's3') {
                unlink($attachment['storage_path']);
            }
        } else {
            Log::error('Exported listing data attachment mail failed to send.');
        }
    }

    /**
     * @return mixed
     */
    private function request(): mixed
    {
        return $this->request;
    }
}
