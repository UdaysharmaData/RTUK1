<?php

namespace App\Console\Commands;

use Log;
use Exception;
use Carbon\Carbon;
use App\Traits\SiteTrait;
use Illuminate\Console\Command;
use App\Traits\AdministratorEmails;
use App\Modules\Setting\Models\Site;
use App\Jobs\handleStripePostPaymentWithoutWebhookJob;
use App\Modules\Finance\Models\OngoingExternalTransaction;
use App\Modules\Finance\Enums\OngoingExternalTransactionStatusEnum;

class handleStripePostPaymentWithoutWebhook extends Command
{
    use AdministratorEmails, SiteTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:post-payment {site}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle stripe post payment without relying on webhook. This helps reduce the time delay when expecting a webhook from stripe before proceeding with processing.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $site = Site::whereName($value = $this->argument('site'))
                ->orWhere('domain', $value)
                ->orWhere('code', $value)
                ->firstOrFail();

            OngoingExternalTransaction::where('site_id', $site->id)
                ->where('status', OngoingExternalTransactionStatusEnum::Pending)
                ->where('updated_at', '>', Carbon::now()->subHour())
                ->chunk(10, function ($transactions) use ($site) {
                    dispatch(new handleStripePostPaymentWithoutWebhookJob($transactions, $site));
                });

            echo "Command ran successfully!";
        } catch (Exception $exception) {
            Log::error($exception);
            $this->error($exception->getMessage());
            return Command::FAILURE;
        }
    
        return Command::SUCCESS;
    }
}
