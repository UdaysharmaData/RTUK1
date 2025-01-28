<?php

namespace App\Console\Commands;

use Log;
use Exception;
use App\Traits\SiteTrait;
use Illuminate\Console\Command;
use App\Traits\AdministratorEmails;
use App\Modules\Setting\Models\Site;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Modules\Participant\Models\Participant;

class UpdateDatabasePriorToLDTMigrationsCommand extends Command
{
    use AdministratorEmails, SiteTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ldt:update-database {site}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate the external_enquiries and participants records in order to fetch back these entries from LDT and have places offered to them.';

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

            ExternalEnquiry::where(function ($query) use ($site) {
                $query->where('site_id', $site->id)
                    ->OrwhereHas('event', function ($query) use ($site) { // Delete the external enquiries for the given site
                        $query->whereHas('eventCategories', function ($query) use ($site) {
                                $query->whereHas('site', function ($query) use ($site) {
                                    $query->where('id', $site->id);
                                });
                            });
                        });
                    })->forceDelete();

            Participant::whereHas('event', function ($query) use ($site) { // Delete the participants for the given site
                    $query->whereHas('eventCategories', function ($query) use ($site) {
                            $query->whereHas('site', function ($query) use ($site) {
                                $query->where('id', $site->id);
                            });
                        });
                })->forceDelete();

            echo "Command ran successfully!";
        } catch (Exception $exception) {
            Log::error($exception);
            $this->error($exception->getMessage());
            return Command::FAILURE;
        }
    
        return Command::SUCCESS;
    }
}
