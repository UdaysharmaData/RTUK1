<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Modules\Charity\Models\CharityMembership;

class UpdateCharityMembershipsStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'memberships:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks if charities memberships has expired and update their status';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        CharityMembership::where('status', CharityMembership::ACTIVE)
            ->chunk(20, function($charityMemberships) {
                foreach ($charityMemberships as $charityMembership) {

                    if (Carbon::parse($charityMembership->expiry_date)->lessThan(Carbon::now())) {
                        $charityMembership->update([ 'status' => CharityMembership::INACTIVE ]);

                        // TODO: Dispatch a job that notifies the charity that it's membership has expired.
                    }
                }
            });
        
        return Command::SUCCESS;
    }
}
