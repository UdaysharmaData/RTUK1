<?php

namespace App\Console\Commands;

use Log;
use Exception;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Modules\Event\Models\Event;
use App\Events\EventsArchivedEvent;
use \App\Traits\AdministratorEmails;
use App\Modules\Setting\Models\Site;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ArchiveExpiredEventsCommand extends Command
{
    use AdministratorEmails;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:archive-expired {site}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive expired events and create an unpublished new copy that will run the next year';

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
            Log::channel($site->code . 'command')->info('Archive Expired Events Process ID: ' . $pid);

            $result = [];

            Event::where('archived', Event::INACTIVE)
                ->whereHas('eventCategories', function ($query) use ($site) {
                    $query->where('end_date', '>', Carbon::now())
                        ->where('site_id', $site->id);
                })->chunk(20, function($events) {
                    foreach ($events as $event) {
                        $result[] = Event::archive($event);
                    }
                });

            if (count($result) > 0) { // TODO: @tsaffi -  Allowed memory size of 536870912 bytes exhausted (tried to allocate 20480 bytes) // Ensure to paginate the chunk or send it as a file when the size is large
                // Notify admin so that the current events can be reviewed and then published
                event(new EventsArchivedEvent($result));
            } else {
                Log::channel($site->code . 'command')->info('No events achived for ' . $site->code);
                echo 'No events were archived for ' . $site->code . "\n";
            }

            Cache::forget('command-site-' . $pid);
            Log::channel($site->code . 'command')->info('Command events:archive-expired ' . $site->code . ' successful!');
            echo 'Command events:archive-expired ' . $site->code . ' successful!'. "\n";
        } catch (ModelNotFoundException $exception) {
            Cache::forget('command-site-' . $pid);
            $this->error($exception->getMessage());
            Log::error($exception);
            Log::channel('command')->info('Command events:archive-expired failed!');
            return Command::FAILURE;
        } catch (Exception $exception) {
            Cache::forget('command-site-' . $pid);
            $this->error($exception->getMessage());
            Log::error($exception);
            Log::channel($site->code . 'command')->info('Command events:archive-expired ' . $site->code . ' failed!');
            echo 'Command events:archive-expired ' . $site->code . ' failed!'. "\n";
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
