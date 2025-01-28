<?php

namespace App\Traits\Sitemap;

use App\Enums\EventStateEnum;
use Log;
use File;
use Exception;
use Carbon\Carbon;
use App\Traits\SiteTrait;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

use App\Models\Page;
use App\Models\City;
use App\Models\Venue;
use App\Models\Region;
use App\Models\Upload;
use App\Models\Sitemap;
use App\Models\Combination;
use App\Modules\Event\Models\Event;
use App\Modules\Setting\Models\Site;
use App\Modules\Charity\Models\Charity;
use App\Modules\Partner\Models\Partner;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Charity\Models\CharityCategory;

use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use App\Services\FileManager\FileManager;

trait SitemapTrait
{
    use SiteTrait;

    /**
     * The path of the sitemap files
     * 
     * @var array
     */
    protected $sitemapPaths = [];

    /** 
     * @return string
     * */
    protected static function xmlHeader(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    }

    /** 
     * @return string
     * */
    protected static function urlsetOpenTag(): string
    {
        return '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    }

    /** 
     * @return string
     * */
    protected static function urlsetCloseTag(): string
    {
        return "\n</urlset>";
    }

    /** 
     * Remove </urlset> tag from xml file. 
     * 
     * @param  string  $path
     * @return bool
     * */
    protected static function removeUrlsetCloseTag(string $path): bool
    {
        $contents = Storage::disk(config('filesystems.default'))->get($path);
        $contents = str_replace('</urlset>', '', $contents);
        $path = Storage::disk(config('filesystems.default'))->put($path, $contents, 'private');

        return true;
    }

    /** 
     * @param  string                   $url
     * @param  null|Carbon              $updatedAt
     * @param  string                   $changeFreq
     * @param  float                    $priority
     * @param  Collection|Upload|null   $uploads
     * @return string
     * */
    protected static function item(string $url, ?Carbon $updatedAt, string $changeFreq, float $priority, Collection|Upload|null $uploads = null): string
    {
        $updatedAt = $updatedAt?->tz('UTC')->toAtomString() ?? Carbon::now()->tz('UTC')->toAtomString();

        $_uploads = null;

        if ($uploads instanceof Upload && $uploads) { // Cast Upload to collection
            $uploads = collect([$uploads]);
        }

        if ($uploads && count($uploads)) {
            foreach ($uploads as $upload) {
                if ($upload->storage_url) {
                    if ($upload->type == UploadTypeEnum::Image) {
                        $_uploads .= "\n\t\t<image:image>\n\t\t\t<image:loc>$upload->storage_url</image:loc>\n\t\t\t<image:title>$upload->title</image:title>\n\t\t</image:image>";
                    } elseif ($upload->type == UploadTypeEnum::Video) {
                        $_uploads .= "\n\t\t<video:video>\n\t\t\t<video:thumbnail_loc>$upload->storage_url</video:thumbnail_loc>\n\t\t\t<video:title>$upload->title</video:title>\n\t\t\t<video:description>$upload->description</video:description>\n\t\t\t<video:content_loc>$upload->storage_url</video:content_loc>\n\t\t</video:video>";
                    } elseif ($upload->type == UploadTypeEnum::Audio) {

                    }
                }
            }
        }

        return "\n\t<url>\n\t\t<loc>$url</loc>\n\t\t<lastmod>$updatedAt</lastmod>\n\t\t<changefreq>$changeFreq</changefreq>\n\t\t<priority>$priority</priority> $_uploads \n\t</url>";
    }

    /** 
     * @return string
     * */
    protected static function header(): string
    {
        return static::xmlHeader().static::urlsetOpenTag();
    }

    /**
     * 
     * @param  Site    $site
     * @param  string  $class
     * @return void
     */
    protected static function updateTimes(Site $site, string $class, bool $updateOldestUpdatedAt = false): void
    {
        $now = Carbon::now();

        $sitemap = Sitemap::updateOrCreate(
            [
                'class_name' => $class,
                'site_id' => $site->id
            ], [
                'latest_updated_at' => $now
            ]
        );

        if ($updateOldestUpdatedAt) {
            $sitemap->oldest_updated_at = $now;
            $sitemap->save();
        }
    }

   /**
     * After latest updated at query scope.
     * 
     * @param  Builder  $query
     * @param  Site     $site
     * @param  string   $class
     * @return Builder
     */
    protected static function afterLatestUpdatedAt(Builder $query, Site $site, string $class): Builder
    {
        $sitemap = Sitemap::where('class_name', $class)
            ->where('site_id', $site->id)
            ->first();

        if ($sitemap?->exists) {
            $query = $query->where('created_at', '>', Carbon::parse($sitemap->latest_updated_at));
        }

        return $query;        
    }

    /**
     * Save the file on disk.
     * 
     * @param  string   $path
     * @param  string   $contents
     * @return void
     */
    protected function saveFile(string $path, string $contents): void
    {
        config(['filesystems.disks.s3.bucket' => config('filesystems.disks.s3.sitemap_bucket')]); // Set the bucket assigned for sitemaps
        config(['filesystems.disks.s3.visibility' => 'private']); // Make the sitemap files private

        Storage::disk(config('filesystems.default'))->put(
            $path,
            $contents,
            'private'
        );
    }

    /**
     * Update the sitemaps
     * 
     * @param  Site  $site
     * @return void
     */
    private function generateIndexSitemap(Site $site): void
    {
        $contents = static::xmlHeader() . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $dateIndex = Carbon::now()->tz('UTC')->toAtomString();

        if (count($this->sitemapPaths)) {
            foreach (array_unique($this->sitemapPaths) as $path) {
                $frontendPath = config('sitemap.'.$site->code.'.frontend_path');
                $_path = $frontendPath . '/' . Str::substr($path, strrpos($path, '/') + 1); // Added to match aws deployment flow for sitemaps because of the way sitemap is setup on our aws hosting. $frontendPath should normally be the same as $path
                $contents .= "\n\t<sitemap>\n\t\t<loc>" . $_path . "</loc>\n\t\t<lastmod>" . $dateIndex . "</lastmod>\n\t</sitemap>";
            }
        }

        $contents .= "\n</sitemapindex>";

        $path = config("sitemap.{$site->code}.path");
        $fileName = 'sitemap.xml';
        $path = $path . '/' . $fileName;

        $this->saveFile($path, $contents);
    }

    /**
     * Regenerate event sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    protected function regenerateEventSiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $found = null;
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == Event::class; })->pluck('file_name')->all();

        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            $path = $path.'/'.$fileName;

            Event::with(['uploads' => function ($query) {
                $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
            }])->select('id', 'slug', 'updated_at')->partnerEvent(Event::ACTIVE)->state(EventStateEnum::Live)
                ->whereHas('eventCategories', function ($query) use ($site) {
                    $query->where('site_id', $site->id);
            })->latest()
            ->chunk(1000, function ($events) use (&$contents, $changeFreq, $priority, &$found) {
                $found = count($events);

                foreach ($events as $event) {
                    $contents .= static::item($event->url, $event->updated_at, $changeFreq, $priority, $event->uploads);
                }
            });

            if ($found) {
                $contents .= static::urlsetCloseTag();

                $this->saveFile($path, $contents);

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file

                static::updateTimes($site, Event::class, true);
            }
        } else {
            Log::channel($site->code . 'sitemap')->warning('Event sitemap file name not found');
        }
    }

    /**
     * Regenerate event category sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    protected function regenerateEventCategorySiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $found = null;
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == EventCategory::class; })->pluck('file_name')->all();

        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            $path = $path.'/'.$fileName;

            EventCategory::with(['uploads' => function ($query) {
                $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
            }])->select('id', 'slug', 'updated_at')
                ->where('site_id', $site->id)
                ->latest()
                ->chunk(1000, function ($categories) use (&$contents, $changeFreq, $priority, &$found) {
                    $found = count($categories);
                    
                    foreach ($categories as $category) {
                        $url = $category->url;
                        $category_url = str_replace("categories", "distances", $url);
                        $contents .= static::item($category_url, $category->updated_at, $changeFreq, $priority, $category->uploads);
                    }
                });

            if ($found) {
                $contents .= static::urlsetCloseTag();

                $this->saveFile($path, $contents);

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file

                static::updateTimes($site, EventCategory::class, true);
            }
        } else {
            Log::channel($site->code . 'sitemap')->warning('Event Category sitemap file name not found');
        }
    }

    /**
     * Regenerate charity sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    protected function regenerateCharitySiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $found = null;
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == Charity::class; })->pluck('file_name')->all();

        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            $path = $path.'/'.$fileName;

            Charity::with(['uploads' => function ($query) {
                $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
            }])->select('id', 'slug', 'updated_at')
                ->latest()
                ->chunk(1000, function ($charities) use (&$contents, $changeFreq, $priority, &$found) {
                    $found = count($charities);

                    foreach ($charities as $charity) {
                        $contents .= static::item($charity->url, $charity->updated_at, $changeFreq, $priority, $charity->uploads);
                    }
                });

            if ($found) {
                $contents .= static::urlsetCloseTag();

                $this->saveFile($path, $contents);

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file

                static::updateTimes($site, Charity::class, true);
            }
        } else {
            Log::channel($site->code . 'sitemap')->warning('Charity sitemap file name not found');
        }
    }

    /**
     * Regenerate charity category sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    protected function regenerateCharityCategorySiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $found = null;
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == CharityCategory::class; })->pluck('file_name')->all();

        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            $path = $path.'/'.$fileName;

            CharityCategory::with(['uploads' => function ($query) {
                $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
            }])->select('id', 'slug', 'updated_at')
                ->latest()
                ->chunk(1000, function ($categories) use (&$contents, $changeFreq, $priority, &$found) {
                    $found = count($categories);

                    foreach ($categories as $category) {
                        $contents .= static::item($category->url, $category->updated_at, $changeFreq, $priority, $category->uploads);
                    }
                });

            if ($found) {
                $contents .= static::urlsetCloseTag();

                $this->saveFile($path, $contents);

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file

                static::updateTimes($site, CharityCategory::class, true);
            }
        } else {
            Log::channel($site->code . 'sitemap')->warning('Charity Category sitemap file name not found');
        }
    }

    /**
     * Regenerate partner sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    protected function regeneratePartnerSiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $found = null;
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == Partner::class; })->pluck('file_name')->all();

        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            $path = $path.'/'.$fileName;

            Partner::with(['upload' => function ($query) {
                $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
            }])->select('id', 'slug', 'updated_at')
                ->where('site_id', $site->id)
                ->latest()
                ->chunk(1000, function ($partners) use (&$contents, $changeFreq, $priority, &$found) {
                    $found = count($partners);

                    foreach ($partners as $partner) {
                        $contents .= static::item($partner->url, $partner->updated_at, $changeFreq, $priority, $partner->upload);
                    }
                });

            if ($found) {
                $contents .= static::urlsetCloseTag();

                $this->saveFile($path, $contents);

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file

                static::updateTimes($site, Partner::class, true);
            }
        } else {
            Log::channel($site->code . 'sitemap')->warning('Partner sitemap file name not found');
        }
    }

    /**
     * Regenerate page sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    protected function regeneratePageSiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $found = null;
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == Page::class; })->pluck('file_name')->all();

        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            $path = $path.'/'.$fileName;

            Page::select('ref', 'site_id', 'updated_at', 'url')
                ->where('site_id', $site->id)
                ->latest()
                ->chunk(1000, function ($pages) use (&$contents, $changeFreq, $priority, &$found) {
                    $found = count($pages);

                    foreach ($pages as $page) {
                        $uploads = collect($this->staticImagesForPages)->where('ref', $page->ref)->map(function ($item) {
                            return (object) [
                                'name' => $item['name'],
                                'type' => $item['type'],
                                'title' => $item['title'],
                                'description' => $item['description'],
                                'storage_url' => Upload::resolveResourceUrl($item['url'])
                            ];
                        });

                        $contents .= static::item($page->url, $page->updated_at, $changeFreq, $priority, $uploads);
                    }
                });

            if ($found) {
                $contents .= static::urlsetCloseTag();

                $this->saveFile($path, $contents);

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file

                static::updateTimes($site, Page::class, true);
            }
        } else {
            Log::channel($site->code . 'sitemap')->warning('Page sitemap file name not found');
        }
    }

    /**
     * Regenerate region sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    protected function regenerateRegionSiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $found = null;
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == Region::class; })->pluck('file_name')->all();

        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            $path = $path.'/'.$fileName;

            Region::with(['uploads' => function ($query) {
                $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
            }])->select('id', 'slug', 'updated_at')
                ->where('site_id', $site->id)
                ->latest()
                ->chunk(1000, function ($regions) use (&$contents, $changeFreq, $priority, &$found) {
                    $found = count($regions);

                    foreach ($regions as $region) {
                        $contents .= static::item($region->url, $region->updated_at, $changeFreq, $priority, $region->uploads);
                    }
                });

            if ($found) {
                $contents .= static::urlsetCloseTag();

                $this->saveFile($path, $contents);

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file

                static::updateTimes($site, Region::class, true);
            }
        } else {
            Log::channel($site->code . 'sitemap')->warning('Region sitemap file name not found');
        }
    }

    /**
     * Regenerate city sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    protected function regenerateCitySiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $found = null;
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == City::class; })->pluck('file_name')->all();

        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            $path = $path.'/'.$fileName;

            City::with(['uploads' => function ($query) {
                $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
            }])->select('id', 'slug', 'updated_at')
                ->where('site_id', $site->id)
                ->latest()
                ->chunk(1000, function ($cities) use (&$contents, $changeFreq, $priority, &$found) {
                    $found = count($cities);

                    foreach ($cities as $city) {
                        $contents .= static::item($city->url, $city->updated_at, $changeFreq, $priority, $city->uploads);
                    }
                });

            if ($found) {
                $contents .= static::urlsetCloseTag();

                $this->saveFile($path, $contents);

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file

                static::updateTimes($site, City::class, true);
            }
        } else {
            Log::channel($site->code . 'sitemap')->warning('City sitemap file name not found');
        }
    }

    /**
     * Regenerate venue sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    protected function regenerateVenueSiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $found = null;
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == Venue::class; })->pluck('file_name')->all();

        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            $path = $path.'/'.$fileName;

            Venue::with(['uploads' => function ($query) {
                $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
            }])->select('id', 'slug', 'updated_at')
                ->where('site_id', $site->id)
                ->latest()
                ->chunk(1000, function ($venues) use (&$contents, $changeFreq, $priority, &$found) {
                    $found = count($venues);

                    foreach ($venues as $venue) {
                        $contents .= static::item($venue->url, $venue->updated_at, $changeFreq, $priority, $venue->uploads);
                    }
                });

            if ($found) {
                $contents .= static::urlsetCloseTag();

                $this->saveFile($path, $contents);

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file

                static::updateTimes($site, Venue::class, true);
            }
        } else {
            Log::channel($site->code . 'sitemap')->warning('Venue sitemap file name not found');
        }
    }

    /**
     * Regenerate combination sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    protected function regenerateCombinationSiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $found = null;
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == Combination::class; })->pluck('file_name')->all();
        
        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            $path = $path.'/'.$fileName;

            Combination::with(['uploads' => function ($query) {
                $query->whereIn('type', [UploadTypeEnum::Image, UploadTypeEnum::Video]);
            }])->select('id', 'slug', 'updated_at', 'path')
                ->where('site_id', $site->id)
                ->latest()
                ->chunk(1000, function ($combinations) use (&$contents, $changeFreq, $priority, &$found) {
                    $found = count($combinations);

                    foreach ($combinations as $combination) {
                        $contents .= static::item($combination->url, $combination->updated_at, $changeFreq, $priority);
                    }
                });

            if ($found) {
                $contents .= static::urlsetCloseTag();

                $this->saveFile($path, $contents);

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file

                static::updateTimes($site, Combination::class, true);
            }
        } else {
            Log::channel($site->code . 'sitemap')->warning('Combination sitemap file name not found');
        }
    }

    /**
     * Regenerate upload sitemap
     * 
     * @param  Site         $site
     * @param  null|string  $contents
     * @param  string       $path
     * @param  string       $changeFreq
     * @param  float        $priority
     * @return void
     */
    protected function regenerateUploadSiteMap(Site $site, null|string $contents, string $path, string $changeFreq, float $priority): void
    {
        $found = null;
        $fileName = collect(config('sitemap.'.$site->code.'.entities'))->filter(function ($entity) { return $entity['model'] == Upload::class; })->pluck('file_name')->all();

        if (isset($fileName[0]) && $fileName = $fileName[0]) {
            $path = $path.'/'.$fileName;

            Upload::select('url', 'updated_at')
                ->where('site_id', $site->id)
                ->where(function ($query) {
                    $query->where(function ($q1) {
                        $q1->where('type', UploadTypeEnum::Image)
                            ->whereHas('uploadables', function ($q2) {
                                $q2->whereIn('use_as', [UploadUseAsEnum::Image, UploadUseAsEnum::Gallery, UploadUseAsEnum::Images, UploadUseAsEnum::WhatIsIncluded, UploadUseAsEnum::RouteInfo, UploadUseAsEnum::Logo]);
                            });
                    })->orWhere('type', UploadTypeEnum::Video)
                        ->orWhere('type', UploadTypeEnum::Audio);
                })->latest()
                ->chunk(1000, function ($uploads) use (&$contents, $changeFreq, $priority, &$found) {
                    $found = count($uploads);

                    foreach ($uploads as $upload) {
                        if ($upload->storageUrl) {
                            $contents .= static::item($upload->storage_url, $upload->updated_at, $changeFreq, $priority, $upload);
                        }
                    }
                });

            if ($found) {
                $contents .= static::urlsetCloseTag();

                $this->saveFile($path, $contents);

                $this->sitemapPaths[] = $path; // Get the path to the sitemap file

                static::updateTimes($site, Upload::class, true);
            }
        } else {
            Log::channel($site->code . 'sitemap')->warning('Upload sitemap file name not found');
        }
    }

    // /**
    //  * Get the model/class associated with the record.
    //  * 
    //  * @param  object       $record
    //  * @return null|string
    //  */
    // protected function getAssociatedModel(object $record): ?string
    // {
    //     $value = null;

    //     if ($record->event || $record->eventCategory) {
    //         $value = 'events';
    //     }

    //     return $value;
    // }

    /**
     * The pages
     * 
     * @var array
     */
    protected $staticImagesForPages = [
        [
            "ref" => "985f5da7-8fbf-4639-b936-e4b913e5aeca",
            "name" => "Our Team",
            "url" => "uploads/public/media/images/r2h1KZ7Ow2jsjMDvrirqhUcb404m2Nd4yucg7T9R.jpg",
            "type" => UploadTypeEnum::Image,
            "title" => "Our Team",
            "description" => "Our Team"
        ],
        [
            "ref" => "985f5da7-8fbf-4639-b936-e4b913e5aeca",
            "name" => "Our Team",
            "url" => "uploads/public/media/images/r2h1KZ7Ow2jsjMDvrirqhUcb404m2Nd4yucg7T9R.jpg",
            "type" => UploadTypeEnum::Image,
            "title" => "Our Team",
            "description" => "Our Team"
        ]
    ];
}