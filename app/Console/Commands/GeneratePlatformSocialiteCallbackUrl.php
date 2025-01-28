<?php

namespace App\Console\Commands;

use App\Modules\Setting\Models\Site;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GeneratePlatformSocialiteCallbackUrl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:make-socials-callback-url {site} {social}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calls an action to get the value for callback URL for specified platform.';

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

            if (is_null($siteKey)) {
                throw new Exception('Platform/Site [key] attribute value not set on model.');
            }
            if (! (config()->has("services.$siteCode.$social"))) {
                throw new Exception('Platform/Site or Social Service is not supported/configured.');
            }

            if (! config()->has('app.url')) {
                throw new Exception('App URL may not be configured. Please update .env or clear config cache.');
            }

            $appUrl = secure_url(rtrim(config('app.url'), '/'));
            $redirectTo = "$appUrl/auth/callback?provider=$social&key=$siteKey";
            $path = base_path('.env');

            if (file_exists($path)) {
                $key = strtoupper($siteCode).'_'.str_replace('-', '_', strtoupper($social)).'_CLIENT_REDIRECT_URL';
                if (! preg_match("/^$key=.*/m", file_get_contents($path))) {
                    $contents = file_get_contents($path);
                    file_put_contents($path, $contents.="$key=$redirectTo");
                } else {
                    file_put_contents($path, preg_replace(
                        "/^$key=.*/m", "$key=$redirectTo", file_get_contents($path)
                    ));
                }
            }

            $this->info($redirectTo); // eg. https://api.test/auth/redirect?provider=github&key=3dceb7d79cc32e664f481f99aee1afd4
        } catch (Exception $exception) {
            Log::error($exception);
            $this->error($exception->getMessage());
        }

        return 0;
    }
}
