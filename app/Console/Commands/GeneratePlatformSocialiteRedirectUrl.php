<?php

namespace App\Console\Commands;

use App\Modules\Setting\Models\Site;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GeneratePlatformSocialiteRedirectUrl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:make-socials-redirect-url {site} {social} {source}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calls an action to get the value for redirect URL for specified platform.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $site = Site::whereName($value = $this->argument('site'))
                ->orWhere('code', $value)
                ->firstOrFail();
            $siteCode = $site->code;
            $siteKey = $site->key;
            $social = $this->argument('social');
            $source = $this->argument('source');

            if (is_null($siteKey)) {
                throw new Exception('Platform/Site [key] attribute value not set on model.');
            }
            if (! (config()->has("services.$siteCode.$social"))) {
                throw new Exception('Platform/Site or Social Service is not supported/configured.');
            }

            if (! config()->has('app.url')) {
                throw new Exception('App URL may not be configured. Please update .env or clear config cache.');
            }

            $supportedSources = config("services.$siteCode.supported_socials_sources", []);
            if (! in_array($source, $supportedSources)) {
                throw new Exception("A valid auth service Source [$source] not specified.");
            }

            $appUrl = secure_url(rtrim(config('app.url'), '/'));
            $redirectTo = "$appUrl/auth/redirect?provider=$social&key=$siteKey&source=$source";

            $this->info($redirectTo); // eg. https://api.test/auth/redirect?provider=github&key=3dceb7d79cc32e664f481f99aee1afd4&source=portal.runthroughhub.com
        } catch (Exception $exception) {
            Log::error($exception);
            $this->error($exception->getMessage());
        }
        return 0;
    }
}
