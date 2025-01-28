<?php

namespace App\Jobs;

use App\Mail\Mail;
use App\Enums\QueueNameEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use App\Modules\Setting\Models\Site;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ResendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $mail;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 3600;

    public Site $site;

    /**
     * Create a new job instance.
     * 
     * @param  mixed  $mail
     * @param  Site   $site
     * @return void
     */
    public function __construct(mixed $mail, Site $site)
    {
        $this->onConnection(QueueNameEnum::High->value); // Set the connection name

        $this->mail = $mail;
        $this->site = $site;
        $this->queue = QueueNameEnum::High->value;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Mail::site($this->site)->send($this->mail);

        Log::debug('Email resent!');
    }
}
