<?php

namespace App\Console;

use Log;
use File;
use Carbon\Carbon;
use App\Modules\Setting\Models\Site;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\Setting\Enums\OrganisationEnum;
use App\Modules\Setting\Models\Organisation;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('external-enquiry:fetch-from-ldt-single runthrough')->everyFifteenMinutes();
        $schedule->command('generate:report-ldt-sync-up runthrough')->dailyAt('22:30');
        
        $this->eeffl($schedule, 'hourly'); // Fetch external enquiries from ldt hourly

        $this->eeffl($schedule, 'everyMinute'); // Fetch external enquiries from ldt every minute

      //  $this->sitemaps($schedule, 'sitemap:regenerate', 'everyTenMinutes'); // Regenerate the sitemaps of the given site. NB: The "sitemap:regenerate" command is registered above the "sitemap:update" command to ensure that "sitemap:regenerate" runs before "sitemap:update" there by increasing the chances of "sitemap:update" not to run whenever an overlap is about to occur.

     //   $this->sitemaps($schedule, 'sitemap:update', 'everyFiveMinutes'); // Update the sitemaps of the given site

        // $this->archiveExpiredEvents($schedule, 'daily'); // Archive expired events

        /*
         * disabling backups: not needed since we're now running on AWS; files are stored in S3 buckets (that support versioning) and data is stored in a managed database service (that supports daily backups)
         * $this->backups($schedule);
         */

        $schedule->command('rolling:update-dates')->daily();

        $this->notifyAdminAndCharityAboutPendingEnquiries($schedule, 'external-enquiry:notify-about-pending', 'daily');
        $this->notifyAdminAndCharityAboutPendingEnquiries($schedule, 'enquiry:notify-about-pending', 'daily');
        if (! app()->environment('production')) {
            $schedule->command('telescope:prune')->everyThirtyMinutes();
        }

        // $this->handleStripePostPaymentWithoutWebhook($schedule, 'stripe:post-payment', 'everyMinute'); // On Hold

        /**
         * Run temporarily
         */
        //$schedule->command('users:sync-related-props runthrough')->withoutOverlapping()->dailyAt('15:00');

        //$schedule->command('uploads:update-data')->withoutOverlapping()->hourly();
        $schedule->command('s3:delete-old-files')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Schedule regular backups
     * 
     * @param  Schedule $schedule
     * @return void
     */
    private function backups(Schedule $schedule): void
    {
        $schedule->command('backup:clean')->daily()->at('01:00');
        $schedule->command('backup:run')->daily()->at('01:30')
            ->onFailure(function () {
                Log::error('System backup has failed!');
            })
            ->onSuccess(function () {
                Log::info('System backup was successful!');
            });
        $schedule->command('backup:monitor')->daily()->at('03:00');
    }

    /**
     * Fetch external enquiries from Lets Do This | For sites belonging to GWActive organisation
     *
     * @param  Schedule  $schedule
     * @param  string    $frequency
     * @return void
     */
    private function eeffl(Schedule $schedule, string $frequency): void
    {
        $sites = Cache::remember('sites', now()->addHour(), function () {
            return Site::whereHas('organisation', function ($query) {
                $query->where('domain', OrganisationEnum::GWActive->value);
            })->get();
        });

        foreach ($sites as $site) {
       
            $schedule->command('external-enquiry:fetch-from-ldt ' . $site->domain)
                ->$frequency()
                ->before(function () use ($frequency, $site) {
                    $frequency == 'hourly' ? Log::channel($site->code . 'ldtfetch')->info('before | hourly') : Log::channel($site->code . 'ldtfetch')->info('before | every minute');
                })
                ->after(function () use ($frequency, $site) {
                    $frequency == 'hourly' ? Log::channel($site->code . 'ldtfetch')->info('after | hourly') : Log::channel($site->code . 'ldtfetch')->info('after | every minute');
                })
                ->onFailure(function () use ($site) {
                    Log::channel($site->code . 'ldtfetch')->error('Fetch participants from LDT has failed!');
                })
                ->onSuccess(function () use ($site) {
                    Log::channel($site->code . 'ldtfetch')->info('Fetch participants from LDT was successful!');
                })
                ->when(function () use ($site, $frequency) {
                    if ($frequency == 'everyMinute') {
                        Log::channel($site->code . 'ldtfetch')->info('when | every minute');

                        return Enquiry::where('updated_at', '>', Carbon::now()->subHour())
                            ->whereNull('participant_id')
                            ->whereHas('site', function ($query) use ($site) {
                                $query->where('domain', $site->domain);
                            })->exists();
                    } else {
                        Log::channel($site->code . 'ldtfetch')->info('when | every hour');
                        return true; // for hourly commands
                    }
                })
                ->createMutexNameUsing('fetch-from-ldt-' . $site->code)
                ->withoutOverlapping();
                // ->runInBackground();
        }
    }

    /**
     * Update/Regenerate sitemaps
     *
     * @param  Schedule  $schedule
     * @param  string    $command
     * @param  string    $frequency // The default frequency value for a given command
     * @return void
     */
    private function sitemaps(Schedule $schedule, string $command, string $frequency): void
    {
        $sites = Cache::remember('sites', now()->addHour(), function () {
            return Site::all();
        });

        foreach ($sites as $site) {
            if (File::exists(config_path('sitemap/'.$site->code.'.php'))) { // Ensure the sitemap config file of the site exists
                if ($command == 'sitemap:update') {
                    $frequency = config('sitemap.' . $site->code . '.update_frequency') ?? $frequency;
                } else if ($command == 'sitemap:regenerate') {
                    $frequency = config('sitemap.' . $site->code . '.regenerate_frequency') ?? $frequency;
                }

                // Update the sitemaps of the given site
                $schedule->command($command . ' ' . $site->domain)
                    ->$frequency()
                    ->onFailure(function () use ($site, $command) {
                        Log::channel($site->code . 'sitemap')->error("{$site->name} {$command} has failed!");
                    })
                    ->onSuccess(function () use ($site, $command) {
                        Log::channel($site->code . 'sitemap')->info("{$site->name} {$command} was successful!");
                    })
                    ->createMutexNameUsing('sitemap-' . $site->code) // Set the mutex name. Helps prevent 2 different commands from overlapping by ensuring their mutex gets created with the same name.
                    ->withoutOverlapping(); // Prevents a command from overlapping with itself.
            }
        }
    }

    /**
     * Notify admin about pending enquiries (external and website)
     *
     * @param  Schedule  $schedule
     * @param  string    $command
     * @param  string    $frequency // The default frequency value for a given command
     * @return void
     */
    private function notifyAdminAndCharityAboutPendingEnquiries(Schedule $schedule, string $command, string $frequency): void
    {
        $sites = Cache::remember('sites', now()->addHour(), function () {
            return Site::all();
        });

        foreach ($sites as $site) {
         
            $schedule->command($command . ' ' . $site->domain)
                ->$frequency()
                ->onFailure(function () use ($site, $command) {
                    Log::channel($site->code . 'enquiry')->error("{$site->name} {$command} has failed!");
                })
                ->onSuccess(function () use ($site, $command) {
                    Log::channel($site->code . 'enquiry')->info("{$site->name} {$command} was successful!");
                })
                ->createMutexNameUsing($command.$site->code) // Set the mutex name. Helps prevent 2 different commands from overlapping by ensuring their mutex gets created with the same name.
                ->withoutOverlapping(); // Prevents a command from overlapping with itself.
        }
    }

    /**
     * Archive expired events
     *
     * @param  Schedule  $schedule
     * @param  string    $frequency // The default frequency value for a given command
     * @return void
     */
    private function archiveExpiredEvents(Schedule $schedule, string $frequency): void
    {
        $command = 'events:archive-expired';

        $sites = Cache::remember('sites', now()->addHour(), function () {
            return Site::all();
        });

        foreach ($sites as $site) {
            $schedule->command($command . ' ' . $site->domain)
                ->$frequency()
                ->onFailure(function () use ($site, $command) {
                    Log::channel($site->code . 'command')->error("{$site->name} {$command} has failed!");
                })
                ->onSuccess(function () use ($site, $command) {
                    Log::channel($site->code . 'command')->info("{$site->name} {$command} was successful!");
                })
                ->createMutexNameUsing('command-'.$site->code) // Set the mutex name. Helps prevent 2 different commands from overlapping by ensuring their mutex gets created with the same name.
                ->withoutOverlapping(); // Prevents a command from overlapping with itself.
        }
    }

    /**
     * Handle stripe post payment without webhook
     *
     * @param  Schedule  $schedule
     * @param  string    $command
     * @param  string    $frequency // The default frequency value for a given command
     * @return void
     */
    private function handleStripePostPaymentWithoutWebhook(Schedule $schedule, string $command, string $frequency): void
    {
        $sites = Cache::remember('sites', now()->addHour(), function () {
            return Site::all();
        });

        foreach ($sites as $site) {
            $schedule->command($command . ' ' . $site->domain)
                ->$frequency()
                ->onFailure(function () use ($site, $command) {
                    Log::channel($site->code . 'command')->error("{$site->name} {$command} has failed!");
                })
                ->onSuccess(function () use ($site, $command) {
                    Log::channel($site->code . 'command')->info("{$site->name} {$command} was successful!");
                })
                ->createMutexNameUsing('command-stripe-'.$site->code) // Set the mutex name. Helps prevent 2 different commands from overlapping by ensuring their mutex gets created with the same name.
                ->withoutOverlapping(); // Prevents a command from overlapping with itself.
        }
    }
}
