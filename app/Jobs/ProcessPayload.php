<?php

namespace App\Jobs;

use App\Models\Payload;
use App\Services\DataExchangeService\Actions\MigratePayloadData;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPayload
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Payload
     */
    private Payload $payload;

    /**
     * @var int
     */
    private int $completedQueryCounter = 0;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Payload $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new MigratePayloadData($this->payload))->process();
    }
}
