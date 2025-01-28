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

class SetDefaultEventRegistrationMethodForExistingRecords extends Command
{
    use AdministratorEmails, SiteTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:set-default-registration-method';

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
            \App\Modules\Event\Models\Event::with('eventThirdParties')
                ->withDrafted()
                ->withTrashed()
                ->chunk(500, function ($events) {
                    foreach ($events as $event) {
                        $registrationMethod = [];
        
                        if (count($event->eventThirdParties)) {
                            $registrationMethod['website_registration_method'] = \App\Modules\Event\Enums\EventRegistrationMethodTypesEnum::External;
                            $registrationMethod['portal_registration_method'] = \App\Modules\Event\Enums\EventRegistrationMethodTypesEnum::Internal;
                        } else {
                            $registrationMethod['website_registration_method'] = \App\Modules\Event\Enums\EventRegistrationMethodTypesEnum::Internal;
                            $registrationMethod['portal_registration_method'] = \App\Modules\Event\Enums\EventRegistrationMethodTypesEnum::Internal;
                        }
        
                        $event->update(['registration_method' => $registrationMethod]);
                    }
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