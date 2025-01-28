<?php

namespace App\Jobs;

use Mail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

use App\Modules\Event\Models\Event;

class UpdateRollingEventsDatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Variables declaration
     *
     * @var Event
     */
    public $event;

    /**
     * Create a new job instance.
     * @param Event $event
     * @return void
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->event->eventCategories as $category) {
            $category->pivot->start_date = Carbon::parse($category->pivot->start_date)->addYear()->toDateTimeString();
            $category->pivot->end_date = Carbon::parse($category->pivot->end_date)->addYear()->toDateTimeString();
            $category->pivot->registration_deadline = Carbon::parse($category->pivot->registration_deadline)?->addYear()->toDateTimeString();
            $category->pivot->withdrawal_deadline = $category->pivot->withdrawal_deadline 
                ? Carbon::parse($category->pivot->withdrawal_deadline)->addYear()->toDateTimeString()
                : (
                    $category->pivot->registration_deadline
                        ? Carbon::parse($category->pivot->registration_deadline)->subWeeks((int) config('app.event_withdrawal_weeks'))->toDateTimeString()
                        : null
                );
            $category->pivot->save();
        }

        // Notify the event managers of the event via email
        // Mail::to()->queue();
    }
}
