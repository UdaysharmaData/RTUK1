<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Cache;
use App\Modules\Setting\Models\Site;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateEventDatesTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $eecs;
    public $site;

    /**
     * Create a new job instance.
     * 
     * @param  mixed  $eecs
     * @param  Site   $site
     * @return void
     */
    public function __construct(mixed $eecs, Site $site)
    {
        $this->eecs = $eecs;
        $this->site = $site;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $pid = getmypid();

        Cache::put('command-site-' . $pid,  $this->site, now()->addHour());

        foreach ($this->eecs as $eec) {
            $eec->start_date = Carbon::parse($eec->start_date)->addYear()->toDateTimeString();
            $eec->end_date = Carbon::parse($eec->end_date)->addYear()->toDateTimeString();
            $eec->registration_deadline = Carbon::parse($eec->registration_deadline)?->addYear()->toDateTimeString();
            $eec->withdrawal_deadline = $eec->withdrawal_deadline
                ? Carbon::parse($eec->withdrawal_deadline)->addYear()->toDateTimeString()
                : ($eec->registration_deadline
                    ? Carbon::parse($eec->registration_deadline)->subWeeks((int) config('app.event_withdrawal_weeks'))->toDateTimeString()
                    : null
                );
            $eec->save();
        }

        Cache::forget('command-site-' . $pid);
    }
}
