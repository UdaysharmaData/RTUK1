<?php

namespace App\Modules\Participant\Observers;

use App\Services\DataCaching\CacheDataManager;
use App\Modules\Participant\Models\Participant;
use App\Services\DataServices\EntryDataService;
use App\Services\DataServices\ParticipantDataService;
use App\Services\DataServices\PartnerEventDataService;
use Illuminate\Support\Facades\Log;

class ParticipantObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Participant "created" event.
     *
     * @param  Participant  $participant
     * @return void
     */
    public function created(Participant $participant)
    {
        CacheDataManager::flushAllCachedServiceListings(new ParticipantDataService);
        CacheDataManager::flushAllCachedServiceListings(new EntryDataService);
        (new CacheDataManager(new PartnerEventDataService(), 'participants'))->flushCachedServiceListings();
    }

    /**
     * Handle the Participant "updated" event.
     *
     * @param  Participant  $participant
     * @return void
     */
    public function updated(Participant $participant)
    {
        CacheDataManager::flushAllCachedServiceListings(new ParticipantDataService);
        CacheDataManager::flushAllCachedServiceListings(new EntryDataService);
        (new CacheDataManager(new PartnerEventDataService(), 'participants'))->flushCachedServiceListings();
    }

    /**
     * Handle the Participant "deleted" event.
     *
     * @param  Participant  $participant
     * @return void
     */
    public function deleted(Participant $participant)
    {
        CacheDataManager::flushAllCachedServiceListings(new ParticipantDataService);
        CacheDataManager::flushAllCachedServiceListings(new EntryDataService);
        (new CacheDataManager(new PartnerEventDataService(), 'participants'))->flushCachedServiceListings();
    }

    /**
     * Handle the Participant "restored" event.
     *
     * @param  Participant  $participant
     * @return void
     */
    public function restored(Participant $participant)
    {
        CacheDataManager::flushAllCachedServiceListings(new ParticipantDataService);
        CacheDataManager::flushAllCachedServiceListings(new EntryDataService);
        (new CacheDataManager(new PartnerEventDataService(), 'participants'))->flushCachedServiceListings();
    }

    /**
     * Handle the Participant "force deleted" event.
     *
     * @param  Participant  $participant
     * @return void
     */
    public function forceDeleted(Participant $participant)
    {
        CacheDataManager::flushAllCachedServiceListings(new ParticipantDataService);
        CacheDataManager::flushAllCachedServiceListings(new EntryDataService);
        (new CacheDataManager(new PartnerEventDataService(), 'participants'))->flushCachedServiceListings();
    }
}
