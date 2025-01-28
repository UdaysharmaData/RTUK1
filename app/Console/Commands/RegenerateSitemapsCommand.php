<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Traits\Sitemap\SitemapTrait;
use Illuminate\Support\Facades\Cache;

use App\Models\Page;
use App\Models\City;
use App\Models\Venue;
use App\Models\Region;
use App\Models\Combination;
use App\Models\Upload;
use App\Modules\Event\Models\Event;
use App\Modules\Setting\Models\Site;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\CharityCategory;
use App\Modules\Partner\Models\Partner;
use App\Modules\Event\Models\EventCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

class RegenerateSitemapsCommand extends Command
{
    use SitemapTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:regenerate {site}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump and regenerate all the sitemaps';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $pid = getmypid();

        try {
            $site = Site::where('name', $this->argument('site'))
                ->orWhere('domain', $this->argument('site'))
                ->orWhere('code', $this->argument('site'))
                ->firstOrFail();

            Cache::put('command-site-' . $pid,  $site, now()->addHour());
            Log::channel($site->code . 'sitemapprocess')->info('Regenerate Process ID: ' . $pid);
    
            $path = config('sitemap.' . $site->code . '.path');

            $_path = $path . '/sitemaps';

            config(['filesystems.disks.s3.bucket' => config('filesystems.disks.s3.sitemap_bucket')]); // Set the bucket assigned for sitemaps
            config(['filesystems.disks.s3.visibility' => 'private']); // Make the sitemap files private
    
            if (Storage::disk(config('filesystems.default'))->directoryMissing($_path)) {
                Storage::disk(config('filesystems.default'))->makeDirectory($_path, 0755); // Create the directory that host all the sitemap files.
            }

            $this->regenerateSiteMaps($site, $_path);

            $this->generateIndexSitemap($site);

            Cache::forget('command-site-' . $pid);
            echo $site->name . ' sitemap regenerate was successful!' . PHP_EOL;
        }  catch (ModelNotFoundException $exception) {
            Cache::forget('command-site-' . $pid);
            Log::error($this->argument('site'));
            Log::error($exception->getMessage());
            Log::error($exception);
            return Command::FAILURE;
        } catch (Exception $exception) {
            Cache::forget('command-site-' . $pid);
            Log::channel($site->code . 'sitemapprocess')->error($exception->getMessage());
            Log::channel($site->code . 'sitemapprocess')->error($exception);
            Log::error($exception);
            echo $site->name . ' sitemap regenerate has failed!' . PHP_EOL ;
            return Command::FAILURE;
        }
    
        return Command::SUCCESS;
    }

    /**
     * Regenerate the sitemaps
     * 
     * @param  Site    $site
     * @param  string  $path
     * @return void
     */
    private function regenerateSiteMaps(Site $site, string $path): void
    {
        $changeFreq = config('sitemap.'.$site->code.'.change_freq');
        $priority = config('sitemap.'.$site->code.'.priority');
        $entities = collect(config('sitemap.'.$site->code.'.entities'))->pluck('model')->toArray();

        $contents = static::header();

        if (in_array(Event::class, $entities)) {
            $this->regenerateEventSiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(EventCategory::class, $entities)) {
            $this->regenerateEventCategorySiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(Charity::class, $entities)) {
            $this->regenerateCharitySiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(CharityCategory::class, $entities)) {
            $this->regenerateCharityCategorySiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(Partner::class, $entities)) {
            $this->regeneratePartnerSiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(Page::class, $entities)) {
            $this->regeneratePageSiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(Region::class, $entities)) {
            $this->regenerateRegionSiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(City::class, $entities)) {
            $this->regenerateCitySiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(Venue::class, $entities)) {
            $this->regenerateVenueSiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(Combination::class, $entities)) {
            $this->regenerateCombinationSiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(Upload::class, $entities)) {
            $this->regenerateUploadSiteMap($site, $contents, $path, $changeFreq, $priority);
        }
    }
}
