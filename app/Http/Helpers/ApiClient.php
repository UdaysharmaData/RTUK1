<?php

use App\Traits\SiteSetting;
use App\Modules\Setting\Models\Site;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ApiClient\ApiClientSettings;

if (! function_exists('client')) {
    function client() {
        return ApiClientSettings::getCurrentClientFromCache();
    }
}

if (! function_exists('clientId')) {
    function clientId() {
        $client = ApiClientSettings::getCurrentClientFromCache();
        return $client?->id;
    }
}

if (! function_exists('clientName')) {
    function clientName() {
        $client = ApiClientSettings::getCurrentClientFromCache();
        return $client?->name;
    }
}

if (! function_exists('clientHost')) {
    function clientHost() {
        $client = ApiClientSettings::getCurrentClientFromCache();
        return $client?->host;
    }
}

if (! function_exists('clientSite')) {
    function clientSite() {
        if (app()->runningInConsole()) {
            if ($site = siteSetting()) {
                return $site;
            } else if (is_array($command = request()->server('argv'))) {
                $arg = end($command);

                if (is_string($arg)) {
                    return Site::where('name', $arg)
                        ->orWhere('domain', $arg)
                        ->orWhere('code', $arg)
                        ->first();
                } else {
                    return null;
                }
            }

            return null;
        }

        if (! is_null($siteId = request('site_id'))) {
            return Site::find($siteId);
        }

        return ApiClientSettings::getCurrentClientSiteFromCache();
    }
}

if (! function_exists('clientSiteId')) {
    function clientSiteId() {
        if (app()->runningInConsole()) {
            if ($site = siteSetting()) {
                return $site->id;
            } else if (is_array($command = request()->server('argv'))) {
                $id = end($command); // First set argument index to get site id from command line

                if (is_int($id)) {
                    return $id;
                } else if (is_string($id)) {
                    return Site::where('name', $id)
                        ->orWhere('domain', $id)
                        ->orWhere('code', $id)
                        ->value('id');
                } else {
                    return null;
                }
            }

            return null;
        }

        if (! is_null($siteId = request('site_id'))) {
            return $siteId;
        }

        $site = ApiClientSettings::getCurrentClientSiteFromCache();
        return $site?->id;
    }
}

if (! function_exists('clientSiteName')) {
    function clientSiteName() {
        $site = ApiClientSettings::getCurrentClientSiteFromCache();
        return $site?->name;
    }
}

if (! function_exists('clientSiteCode')) {
    function clientSiteCode() {
        $site = ApiClientSettings::getCurrentClientSiteFromCache();
        return $site?->code;
    }
}

if (! function_exists('site')) {
    function site() {
        return ApiClientSettings::getCurrentClientSiteFromCache();
    }
}

if (! function_exists('siteSetting')) {
    function siteSetting() {
        $pid = getmypid();

        $cache = Cache::get('command-site-' . $pid);

        Log::channel('processes')->info('Process ID: ' . $pid);
        Log::channel('processes')->info('From Cache: ' . $cache);

        return $cache;
    }
}
