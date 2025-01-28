<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Page;
use App\Models\City;
use App\Models\Venue;
use App\Models\Region;
use App\Models\Upload;
use App\Models\Combination;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use Illuminate\Console\Command;
use App\Modules\Event\Models\Event;
use Illuminate\Support\Facades\Log;
use App\Modules\Setting\Models\Site;
use App\Traits\Sitemap\SitemapTrait;
use Illuminate\Support\Facades\Cache;
use App\Modules\Partner\Models\Partner;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Charity\Models\CharityCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

class UpdateSitemapsCommand extends Command
{
    use SitemapTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:update {site}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update (Regenerate if file is not found) the sitemaps with the recently created data';

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
            Log::channel($site->code . 'sitemapprocess')->info('Update Process ID: ' . $pid);

            $path = config('sitemap.'.$site->code.'.path');

            $_path = $path . '/sitemaps';

            config(['filesystems.disks.s3.bucket' => config('filesystems.disks.s3.sitemap_bucket')]); // Set the bucket assigned for sitemaps
            config(['filesystems.disks.s3.visibility' => 'private']); // Make the sitemap files private
    
            if (Storage::disk(config('filesystems.default'))->directoryMissing($_path)) {
                Storage::disk(config('filesystems.default'))->makeDirectory($_path, 0755); // Create the directory that host all the sitemap files.
            }

            $this->updateSiteMaps($site, $_path);

            $this->generateIndexSitemap($site);

            Cache::forget('command-site-' . $pid);
            echo $site->name . ' sitemap update was successful!' . PHP_EOL;
        } catch (ModelNotFoundException $exception) {
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
            echo $site->name . ' sitemap update has failed!' . PHP_EOL;
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Update the sitemaps
     * 
     * @param  Site    $site
     * @param  string  $path
     * @return void
     */
    private function updateSiteMaps(Site $site, string $path): void
    {
        $changeFreq = config('sitemap.'.$site->code.'.change_freq');
        $priority = config('sitemap.'.$site->code.'.priority');
        $entities = collect(config('sitemap.'.$site->code.'.entities'))->pluck('model')->toArray();

        $contents = null;

        if (in_array(Event::class, $entities)) {
            static::updateEventSiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(EventCategory::class, $entities)) {
            static::updateEventCategorySiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(Charity::class, $entities)) {
            static::updateCharitySiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(CharityCategory::class, $entities)) {
            static::updateCharityCategorySiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(Partner::class, $entities)) {
            static::updatePartnerSiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(Page::class, $entities)) {
            static::updatePageSiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(Region::class, $entities)) {
            static::updateRegionSiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(City::class, $entities)) {
            static::updateCitySiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(Venue::class, $entities)) {
            static::updateVenueSiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(Combination::class, $entities)) {
            static::updateCombinationSiteMap($site, $contents, $path, $changeFreq, $priority);
        }

        if (in_array(Upload::class, $entities)) {
            static::updateUploadSiteMap($site, $contents, $path, $changeFreq, $priority);
        }
    }

    /**
     * Update event sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    private function updateEventSiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == Event::class; })->pluck('file_name')->all();

        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            if (Storage::disk(config('filesystems.default'))->exists($path.'/'.$fileName)) { // Check if the file exists on disk
                $path = $path.'/'.$fileName;

                $events = Event::with(['uploads' => function ($query) {
                    $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
                }])->select('id', 'slug', 'updated_at')
                    ->whereHas('eventCategories', function ($query) use ($site) {
                        $query->where('site_id', $site->id);
                });

                $events = static::afterLatestUpdatedAt($events, $site, Event::class);

                $events = $events->latest()
                    ->chunk(1000, function ($events) use (&$contents, $changeFreq, $priority) {
                        foreach ($events as $event) {
                            $contents .= static::item($event->url, $event->updated_at, $changeFreq, $priority, $event->uploads);
                        }
                    });

                if ($contents) { // Only update the sitemap when there are new records
                    static::updateSiteMap($path, $contents);
                }

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file
            } else { //  Regenerate sitemap if file does not exists
                $contents = static::header();
                $this->regenerateEventSiteMap($site, $contents, $path, $changeFreq, $priority);
            }
            
            static::updateTimes($site, Event::class);
        } else {
            Log::channel($site->code . 'sitemap')->warning('Event sitemap file name not found');
        }
    }

    /**
     * Update event category sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    private function updateEventCategorySiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == EventCategory::class; })->pluck('file_name')->all();

        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            if (Storage::disk(config('filesystems.default'))->exists($path.'/'.$fileName)) { // Check if the file exists on disk
                $path = $path.'/'.$fileName;
            
                $categories = EventCategory::with(['uploads' => function ($query) {
                    $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
                }])->select('id', 'slug', 'updated_at')
                    ->where('site_id', $site->id);

                $categories = static::afterLatestUpdatedAt($categories, $site, Event::class);

                $categories = $categories->latest()
                    ->chunk(1000, function ($categories) use (&$contents, $changeFreq, $priority) {
                        foreach ($categories as $category) {
                            $contents .= static::item($category->url, $category->updated_at, $changeFreq, $priority, $category->uploads);
                        }
                    });

                if ($contents) { // Only update the sitemap when there are new records
                    static::updateSiteMap($path, $contents);
                }

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file
            } else { //  Regenerate sitemap if file does not exists
                $contents = static::header();
                $this->regenerateEventCategorySiteMap($site, $contents, $path, $changeFreq, $priority);
            }
            
            static::updateTimes($site, EventCategory::class);
        } else {
            Log::channel($site->code . 'sitemap')->warning('Event Category sitemap file name not found');
        }
    }

    /**
     * Update charity sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    private function updateCharitySiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == Charity::class; })->pluck('file_name')->all();
        
        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            if (Storage::disk(config('filesystems.default'))->exists($path.'/'.$fileName)) { // Check if the file exists on disk
                $path = $path.'/'.$fileName;

                $charities = Charity::with(['uploads' => function ($query) {
                    $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
                }])->select('id', 'slug', 'updated_at');

                $charities = static::afterLatestUpdatedAt($charities, $site, Charity::class);

                $charities = $charities->latest()
                    ->chunk(1000, function ($charities) use (&$contents, $changeFreq, $priority) {
                        foreach ($charities as $charity) {
                            $contents .= static::item($charity->url, $charity->updated_at, $changeFreq, $priority, $charity->uploads);
                        }
                    });

                if ($contents) { // Only update the sitemap when there are new records
                    static::updateSiteMap($path, $contents);
                }

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file
            } else { //  Regenerate sitemap if file does not exists
                $contents = static::header();
                $this->regenerateCharitySiteMap($site, $contents, $path, $changeFreq, $priority);
            }
            
            static::updateTimes($site, Charity::class);
        } else {
            Log::channel($site->code . 'sitemap')->warning('Charity sitemap file name not found');
        }
    }

    /**
     * Update charity category sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    private function updateCharityCategorySiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == CharityCategory::class; })->pluck('file_name')->all();

        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            if (Storage::disk(config('filesystems.default'))->exists($path.'/'.$fileName)) { // Check if the file exists on disk
                $path = $path.'/'.$fileName;

                $categories = CharityCategory::with(['uploads' => function ($query) {
                    $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
                }])->select('id', 'slug', 'updated_at');

                $categories = static::afterLatestUpdatedAt($categories, $site, CharityCategory::class);

                $categories = $categories->latest()
                    ->chunk(1000, function ($categories) use (&$contents, $changeFreq, $priority) {
                        foreach ($categories as $category) {
                            $contents .= static::item($category->url, $category->updated_at, $changeFreq, $priority, $category->uploads);
                        }
                    });

                if ($contents) { // Only update the sitemap when there are new records
                    static::updateSiteMap($path, $contents);
                }

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file
            } else { //  Regenerate sitemap if file does not exists
                $contents = static::header();
                $this->regenerateCharityCategorySiteMap($site, $contents, $path, $changeFreq, $priority);
            }
            
            static::updateTimes($site, CharityCategory::class);
        } else {
            Log::channel($site->code . 'sitemap')->warning('Charity Category sitemap file name not found');
        }
    }

    /**
     * Update partner sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    private function updatePartnerSiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == Partner::class; })->pluck('file_name')->all();
        
        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            if (Storage::disk(config('filesystems.default'))->exists($path.'/'.$fileName)) { // Check if the file exists on disk
                $path = $path.'/'.$fileName;

                $partners = Partner::with(['upload' => function ($query) {
                    $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
                }])->select('id', 'slug', 'updated_at')
                    ->where('site_id', $site->id);

                $partners = static::afterLatestUpdatedAt($partners, $site, Partner::class);

                $partners = $partners->latest()
                    ->chunk(1000, function ($partners) use (&$contents, $changeFreq, $priority) {
                        foreach ($partners as $partner) {
                            $contents .= static::item($partner->url, $partner->updated_at, $changeFreq, $priority, $partner->upload);
                        }
                    });

                if ($contents) { // Only update the sitemap when there are new records
                    static::updateSiteMap($path, $contents);
                }

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file
            } else { //  Regenerate sitemap if file does not exists
                $contents = static::header();
                $this->regeneratePartnerSiteMap($site, $contents, $path, $changeFreq, $priority);
            }
            
            static::updateTimes($site, Partner::class);
        } else {
            Log::channel($site->code . 'sitemap')->warning('Partner sitemap file name not found');
        }
    }

    /**
     * Update page sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    private function updatePageSiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == Page::class; })->pluck('file_name')->all();

        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            if (Storage::disk(config('filesystems.default'))->exists($path.'/'.$fileName)) { // Check if the file exists on disk
                $path = $path.'/'.$fileName;

                $pages = Page::select('updated_at', 'url')
                    ->where('site_id', $site->id);

                $pages = static::afterLatestUpdatedAt($pages, $site, Page::class);

                $pages = $pages->latest()
                    ->chunk(1000, function ($pages) use (&$contents, $changeFreq, $priority) {
                        foreach ($pages as $page) {
                            $contents .= static::item($page->url, $page->updated_at, $changeFreq, $priority);
                        }
                    });

                if ($contents) { // Only update the sitemap when there are new records
                    static::updateSiteMap($path, $contents);
                }

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file
            } else { //  Regenerate sitemap if file does not exists
                $contents = static::header();
                $this->regeneratePageSiteMap($site, $contents, $path, $changeFreq, $priority);
            }
            
            static::updateTimes($site, Page::class);
        } else {
            Log::channel($site->code . 'sitemap')->warning('Page sitemap file name not found');
        }
    }

    /**
     * Update region sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    private function updateRegionSiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == Region::class; })->pluck('file_name')->all();
        
        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            if (Storage::disk(config('filesystems.default'))->exists($path.'/'.$fileName)) { // Check if the file exists on disk
                $path = $path.'/'.$fileName;

                $regions = Region::with(['uploads' => function ($query) {
                    $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
                }])->select('id', 'slug', 'updated_at')
                    ->where('site_id', $site->id);

                $regions = static::afterLatestUpdatedAt($regions, $site, Region::class);

                $regions = $regions->latest()
                    ->chunk(1000, function ($regions) use (&$contents, $site, $changeFreq, $priority) {
                        foreach ($regions as $region) {
                            $contents .= static::item($region->url, $region->updated_at, $changeFreq, $priority, $region->uploads);
                        }
                    });

                if ($contents) { // Only update the sitemap when there are new records
                    static::updateSiteMap($path, $contents);
                }

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file
            } else { //  Regenerate sitemap if file does not exists
                $contents = static::header();
                $this->regenerateRegionSiteMap($site, $contents, $path, $changeFreq, $priority);
            }
            
            static::updateTimes($site, Region::class);
        } else {
            Log::channel($site->code . 'sitemap')->warning('Region sitemap file name not found');
        }
    }

    /**
     * Update city sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    private function updateCitySiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == City::class; })->pluck('file_name')->all();
        
        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            if (Storage::disk(config('filesystems.default'))->exists($path.'/'.$fileName)) { // Check if the file exists on disk
                $path = $path.'/'.$fileName;

                $cities = City::with(['uploads' => function ($query) {
                    $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
                }])->select('id', 'slug', 'updated_at')
                    ->where('site_id', $site->id);

                $cities = static::afterLatestUpdatedAt($cities, $site, City::class);

                $cities = $cities->latest()
                    ->chunk(1000, function ($cities) use (&$contents, $site, $changeFreq, $priority) {
                        foreach ($cities as $city) {
                            $contents .= static::item($city->url, $city->updated_at, $changeFreq, $priority, $city->uploads);
                        }
                    });

                if ($contents) { // Only update the sitemap when there are new records
                    static::updateSiteMap($path, $contents);
                }

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file
            } else { //  Regenerate sitemap if file does not exists
                $contents = static::header();
                $this->regenerateCitySiteMap($site, $contents, $path, $changeFreq, $priority);
            }
            
            static::updateTimes($site, City::class);
        } else {
            Log::channel($site->code . 'sitemap')->warning('City sitemap file name not found');
        }
    }

    /**
     * Update venue sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    private function updateVenueSiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == Venue::class; })->pluck('file_name')->all();
        
        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            if (Storage::disk(config('filesystems.default'))->exists($path.'/'.$fileName)) { // Check if the file exists on disk
                $path = $path.'/'.$fileName;

                $venues = Venue::with(['uploads' => function ($query) {
                    $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
                }])->select('id', 'slug', 'updated_at')
                    ->where('site_id', $site->id);

                $venues = static::afterLatestUpdatedAt($venues, $site, Venue::class);

                $venues = $venues->latest()
                    ->chunk(1000, function ($venues) use (&$contents, $site, $changeFreq, $priority) {
                        foreach ($venues as $venue) {
                            $contents .= static::item($venue->url, $venue->updated_at, $changeFreq, $priority, $venue->uploads);
                        }
                    });

                if ($contents) { // Only update the sitemap when there are new records
                    static::updateSiteMap($path, $contents);
                }

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file
            } else { //  Regenerate sitemap if file does not exists
                $contents = static::header();
                $this->regenerateVenueSiteMap($site, $contents, $path, $changeFreq, $priority);
            }
            
            static::updateTimes($site, Venue::class);
        } else {
            Log::channel($site->code . 'sitemap')->warning('Venue sitemap file name not found');
        }
    }

    /**
     * Update combination sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    private function updateCombinationSiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == Combination::class; })->pluck('file_name')->all();
        
        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            if (Storage::disk(config('filesystems.default'))->exists($path.'/'.$fileName)) { // Check if the file exists on disk
                $path = $path.'/'.$fileName;

                $combinations = Combination::with(['uploads' => function ($query) {
                    $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
                }])->select('id', 'slug', 'updated_at')
                    ->where('site_id', $site->id);

                $combinations = static::afterLatestUpdatedAt($combinations, $site, Combination::class);

                $combinations = $combinations->latest()
                    ->chunk(1000, function ($combinations) use (&$contents, $site, $changeFreq, $priority) {
                        foreach ($combinations as $combination) {
                            $contents .= static::item($combination->url, $combination->updated_at, $changeFreq, $priority, $combination->uploads);
                        }
                    });

                if ($contents) { // Only update the sitemap when there are new records
                    static::updateSiteMap($path, $contents);
                }

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file
            } else { //  Regenerate sitemap if file does not exists
                $contents = static::header();
                $this->regenerateCombinationSiteMap($site, $contents, $path, $changeFreq, $priority);
            }
            
            static::updateTimes($site, Combination::class);
        } else {
            Log::channel($site->code . 'sitemap')->warning('Combination sitemap file name not found');
        }
    }

    /**
     * Update upload sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    private function updateUploadSiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == Upload::class; })->pluck('file_name')->all();

        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            if (Storage::disk(config('filesystems.default'))->exists($path.'/'.$fileName)) { // Check if the file exists on disk
                $path = $path.'/'.$fileName;

                $uploads = Upload::select('url', 'updated_at')
                    ->where('site_id', $site->id)
                    ->where(function ($query) {
                        $query->where(function ($q1) {
                            $q1->where('type', UploadTypeEnum::Image)
                                ->whereHas('uploadables', function ($q2) {
                                    $q2->whereIn('use_as', [UploadUseAsEnum::Image, UploadUseAsEnum::Gallery, UploadUseAsEnum::Images, UploadUseAsEnum::WhatIsIncluded, UploadUseAsEnum::RouteInfo, UploadUseAsEnum::Logo]);
                                });
                        })->orWhere('type', UploadTypeEnum::Video)
                            ->orWhere('type', UploadTypeEnum::Audio);
                    });

                $uploads = static::afterLatestUpdatedAt($uploads, $site, Upload::class);

                $uploads = $uploads->latest()
                    ->chunk(1000, function ($uploads) use (&$contents, $site, $changeFreq, $priority) {
                        foreach ($uploads as $upload) {
                            if ($upload->storageUrl) {
                                $contents .= static::item($upload->storage_url, $upload->updated_at, $changeFreq, $priority);
                            }
                        }
                    });

                if ($contents) { // Only update the sitemap when there are new records
                    static::updateSiteMap($path, $contents);
                }

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file
            } else { //  Regenerate sitemap if file does not exists
                $contents = static::header();
                $this->regenerateUploadSiteMap($site, $contents, $path, $changeFreq, $priority);
            }
            
            static::updateTimes($site, Upload::class);
        } else {
            Log::channel($site->code . 'sitemap')->warning('Upload sitemap file name not found');
        }
    }

    /**
     * Update sitemap
     * 
     * @param  string  $path
     * @param  string  $contents
     * @return void
     */
    private static function updateSiteMap(string $path, string $contents): void
    {
        static::removeUrlsetCloseTag($path); // Remove </urlset> tag

        $contents .= static::urlsetCloseTag(); // Append </urlset> tag to the content

        Storage::disk(config('filesystems.default'))->append($path, $contents); // Append the recently created records to the .xml file
    }
}
