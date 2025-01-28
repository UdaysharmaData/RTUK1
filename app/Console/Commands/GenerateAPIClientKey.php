<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateAPIClientKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:generate-key {client}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calls an action to get the value for the [X-Client-Key] header used for validating API request client.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $client = ApiClient::whereName($this->argument('client'))->firstOrFail();
            $hash = hash_hmac('sha256', $client->api_client_id, config('app.key'));
            $this->info($hash);

            $path = base_path('.env');

            if (file_exists($path)) { // Update .env X_Client_Key value with the client to be used for scribe documentation
                file_put_contents($path, str_replace(
                    'X_Client_Key='.$this->laravel['config']['apiclient.x-client-key'], 'X_Client_Key='.$hash, file_get_contents($path)
                ));
            }
        } catch (Exception $exception) {
            Log::error($exception);
            $this->error($exception->getMessage());
        }
        return 0;
    }
}
