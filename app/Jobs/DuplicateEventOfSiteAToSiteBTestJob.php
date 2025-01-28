<?php

namespace App\Jobs;

use App\Enums\PredefinedPartnersEnum;
use DB;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use App\Modules\Event\Models\Event;
use Illuminate\Support\Facades\Cache;
use App\Modules\Setting\Models\Site;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Event\Models\EventCategoryEventThirdParty;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Event\Models\EventThirdParty;
use App\Modules\Partner\Models\Partner;
use App\Modules\Partner\Models\PartnerChannel;

class DuplicateEventOfSiteAToSiteBTestJob //implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $event;
    public $siteA;
    public $siteB;

    /**
     * Create a new job instance.
     * 
     * @param  Event  $event
     * @param  Site   $siteA
     * @param  Site   $siteB
     * @return void
     */
    public function __construct(Event $event, Site $siteA, Site $siteB)
    {
        $this->event = $event;
        $this->siteA = $siteA;
        $this->siteB = $siteB;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            DB::beginTransaction();

            $pid = getmypid();

            Cache::put('command-site-' . $pid,  $this->siteB, now()->addHour());

            $event = new Event($this->event->toArray());
            $event->save();

            $eecs = EventEventCategory::with('eventCategory')
                ->where('event_id', $this->event->id)
                ->get();

            foreach ($eecs as $eec) {
                $_eec = new EventEventCategory($eec->toArray());
                $_eec->event_id = $event->id;
                $_eec->event_category_id = EventCategory::where('site_id', $this->siteB)->where('name', $eec->eventCategory?->name)->value('id');
                $_eec->save();
            }

            $eventThirdParties = EventThirdParty::with('eventCategories')
                ->where('event_id', $this->event->id)
                ->get();

            foreach ($eventThirdParties as $thirdParty) {
                $_thirdParty = EventThirdParty::create([
                    'event_id' => $event->id,
                    'partner_channel_id' => PartnerChannel::whereHas('partner', function ($query) {
                        $query->where('site_id', $this->siteB->id)
                            ->where('code', PredefinedPartnersEnum::LetsDoThis->value);
                    })->value('id') ?? $this->createPartnerAndPartnerChannel(),
                    'external_id' => $thirdParty->external_id,
                ]);

                foreach ($thirdParty->eventCategories as $_category) {
                    EventCategoryEventThirdParty::create([
                        'event_third_party_id' => $_thirdParty->id,
                        'event_category_id' => EventCategory::where('site_id', $this->siteB)->where('name', $_category->name)->value('id'),
                        'external_id' => $_category->pivot->external_id
                    ]);
                }
            }

            Cache::forget('command-site-' . $pid);
            DB::commit();
        } catch (\Exception $e) {
            \Log::debug($e);
            DB::rollback();
        }
    }

    /**
     * Create partner and partner channel
     * 
     * @return int
     */
    private function createPartnerAndPartnerChannel(): int
    {
        $partner = Partner::create([
            'site_id' => $this->siteB->id,
            'name' => 'Lets Do This',
            'slug' => 'lets-do-this',
            'code' => 'lets-do-this',
            'website' => 'https://www.letsdothis.com',
            'description' => 'Lets Do This'
        ]);

        $partnerChannel = PartnerChannel::create([
            'partner_id' => $partner->id,
            'name' => 'All',
            'code' => 'all'
        ]);

        return $partnerChannel->id;
    }
}
