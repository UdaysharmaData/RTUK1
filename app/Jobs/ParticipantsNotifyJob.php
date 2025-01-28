<?php

namespace App\Jobs;

use App\Mail\Mail;
use App\Traits\SiteTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Mail\ParticipantNotifyMail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Modules\Participant\Models\Participant;

class ParticipantsNotifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SiteTrait;

    public $participants;

    /**
     * Create a new job instance.
     * 
     * @param  Collection $participants
     * @return void
     */
    public function __construct(Collection $participants)
    {
        $this->participants = $participants;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->participants as $participant) {
            $participant->load(['user.profile.participantProfile', 'event']);

            $_participant = Participant::find($participant->id);

            try {
                Mail::site()->to($_participant->user->email)->queue(new ParticipantNotifyMail($_participant)); // Notify the participant via email
            } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                Log::channel(static::getSite()?->code . 'mailexception')->info("Notify Participants");
                Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                dispatch(new ResendEmailJob(new ParticipantNotifyMail($_participant), clientSite()));
            } catch (\Exception $e) {
                Log::channel(static::getSite()?->code . 'mailexception')->info("Notify Participants");
                Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                dispatch(new ResendEmailJob(new ParticipantNotifyMail($_participant), clientSite()));
            }
        }
    }
}
