<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Enums\EventTypeEnum;
use App\Enums\EventStateEnum;
use Illuminate\Console\Command;
use App\Modules\Event\Models\Event;
use App\Jobs\UpdateRollingEventsDatesJob;

class UpdateRollingEventsDatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rolling:update-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks and updates the dates (start_date, end_date, registration_deadline, withdrawal_deadline) of rolling events that have expired (end_date < now()) to the same date and month for the upcoming year';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Event::where('status', Event::ACTIVE)
            ->state(EventStateEnum::Expired)
            ->where('type', EventTypeEnum::Rolling)
            ->chunk(20, function($events) {
                foreach ($events as $event) {
                    dispatch(new UpdateRollingEventsDatesJob($event));
                }
            });
        
        return Command::SUCCESS;
    }
}
