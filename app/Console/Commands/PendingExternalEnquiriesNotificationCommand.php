<?php

namespace App\Console\Commands;

use Log;
use Exception;
use Carbon\Carbon;
use Illuminate\Console\Command;
use \App\Traits\AdministratorEmails;
use App\Modules\Setting\Models\Site;
use Illuminate\Support\Facades\Cache;
use App\Modules\Enquiry\Models\ExternalEnquiry;

class PendingExternalEnquiriesNotificationCommand extends Command
{
    use AdministratorEmails;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'external-enquiry:notify-about-pending {site}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify admin(s) about fetched external enquiries that could not be converted to participants — their emails (details) are null.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $pid = getmypid();

        try {
            $site = Site::whereName($value = $this->argument('site'))
                ->orWhere('domain', $value)
                ->orWhere('code', $value)
                ->firstOrFail();

            Cache::put('command-site-' . $pid,  $site, now()->addHour());
            Log::channel($site->code . 'enquiryprocess')->info('Pending External Enquiries Process ID: ' . $pid);

            if ($count = ExternalEnquiry::whereNull('participant_id')
                ->whereDate('created_at', Carbon::yesterday())
                ->whereHas('site', function ($query) use ($site) {
                    $query->where('id', $site->id);
                })->count()) {
                // TODO: Email the admin about this count
            }

//        static::sendEmails(new \App\Mail\enquiry\external\ldt\FailedToOfferPlacesMail(25));
            Cache::forget('command-site-' . $pid);
        } catch (Exception $exception) {
            Cache::forget('command-site-' . $pid);
            Log::error($exception);
            $this->error($exception->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
