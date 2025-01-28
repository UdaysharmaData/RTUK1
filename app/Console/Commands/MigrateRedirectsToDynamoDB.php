<?php

namespace App\Console\Commands;

use Aws\DynamoDb\Marshaler;
use Illuminate\Console\Command;
use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Support\Facades\DB;
use App\Modules\Setting\Models\Site;
use App\Models\Redirect;  // Your MySQL model

class MigrateRedirectsToDynamoDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redirects:inserting {site}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate redirect data from MySQL to DynamoDB';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $site = Site::where('name', $this->argument('site'))
            ->orWhere('domain', $this->argument('site'))
            ->orWhere('code', $this->argument('site'))
            ->firstOrFail();

        $aws_credentials = config('services.ses');
        $client = new DynamoDbClient([
            'region'  => $aws_credentials['region'],
            'version' => 'latest',
        ]);

        $marshaler = new Marshaler();
        $redirects = DB::table('redirects')
            ->where('site_id', $site->id)
            ->where(function ($query) {
                $query->where('is_process', 0)
                    ->where('resync_again', 0);
            })
            ->orWhere(function ($query) {
                $query->where('is_process', 0)
                    ->orWhere('resync_again', 1);
            })
            ->get();

        // DynamoDB table name
        $tableName =  $aws_credentials['table_name'];
        foreach ($redirects as $redirect) {
            $path = parse_url($redirect->target_url, PHP_URL_PATH);
            $data = [
                'id' =>  $redirect->id,
                'site_id' =>  clientSiteId(),
                'redirect_url' => $redirect->redirect_url,
                'target_url' => $redirect->target_url,
                'target_path' =>  $path,
                'http_code' => 301,
                'active' =>  $redirect->is_active,
                'created_at' => $redirect->created_at,
                'updated_at' => $redirect->updated_at,
            ];
            try {
                $updateData = ['is_process' => 1, 'resync_again' => 1];
                if ($redirect->is_process == 0) {
                    if ($redirect->resync_again == 0) {
                        $client->putItem([
                            'TableName' => $tableName,
                            'Item' => $marshaler->marshalItem($data),
                        ]);
                    } elseif ($redirect->resync_again == 1) {
                        $key = ['id' => ['S' => (string)$redirect->id]];
                        $updateExpression = 'SET is_process = :is_process, resync_again = :resync_again';
                        $expressionAttributeValues = [
                            ':is_process' => ['BOOL' => true],
                            ':resync_again' => ['N' => 1],
                        ];
                        $client->updateItem([
                            'TableName' => $tableName,
                            'Key' => $key,
                            'UpdateExpression' => $updateExpression,
                            'ExpressionAttributeValues' => $expressionAttributeValues,
                        ]);
                    }
                    DB::table('redirects')->where('id', $redirect->id)->update($updateData);
                }
                $this->info("Inserted redirect ID {$redirect->id} successfully.");
            } catch (\Exception $e) {
                $this->error("Failed to insert redirect ID {$redirect->id}: " . $e->getMessage());
            }
        }
        return 0;
    }
}
