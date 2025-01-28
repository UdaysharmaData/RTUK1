<?php

namespace App\Services\DataServices;

use App\Jobs\ProcessDataServiceExport;
use App\Traits\Response;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Enums\EventStateEnum;
use App\Enums\EventCharitiesEnum;
use App\Enums\CharityUserTypeEnum;
use App\Enums\EventCategoryVisibilityEnum;
use App\Enums\PredefinedPartnersEnum;
use App\Http\Helpers\AccountType;
use App\Modules\Event\Models\Event;
use App\Modules\Participant\Models\Participant;
use App\Modules\Event\Models\EventEventCategory;
use App\Services\ExportManager\FileExporterService;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\ExportManager\Formatters\ParticipantExportableDataFormatter;
use App\Modules\Event\Requests\PartnerEventParticipantsListingQueryParamsRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PartnerEventDataService extends DataService implements DataServiceInterface
{
    use Response;

    /**
     * @param  mixed  $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder
    {
        return $this->getFilteredEventsQuery($request);
    }

    /**
     * @param  mixed                 $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedList(mixed $request): LengthAwarePaginator
    {
        return $this->paginate($this->getFilteredQuery($request));
    }

    /**
     * @param  mixed  $request
     * @return Builder
     */
    public function getFilteredParticipantsQuery(mixed $request): Builder
    {
        return $this->_getFilteredParticipantsQuery($request);
    }

    /**
     * @param  mixed                 $request
     * @return LengthAwarePaginator
     */
    public function participants(mixed $request): LengthAwarePaginator
    {
        return $this->paginate($this->_getFilteredParticipantsQuery($request));
    }

    /**
     * @param  mixed  $request
     * @return \Illuminate\Database\Eloquent\Collection|Builder
     */
    public function getExportList(mixed $request): Builder|\Illuminate\Database\Eloquent\Collection
    {
        return $this->_getFilteredParticipantsQuery($request)->get();
    }

    /**
     * @param mixed $request
     * @return array|JsonResponse|BinaryFileResponse|StreamedResponse
     */
    public function downloadCsv(mixed $request): array|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        ProcessDataServiceExport::dispatch(
            (new FileExporterService(
                $this,
                new ParticipantExportableDataFormatter,
                ucfirst(Event::where('ref', $request->event)->value('name')) . ' - Participants'
            )),
            json_encode($request),
            $request->user()
        );

        return $this->success('The exported file will be sent to your email shortly.');

//        return (new FileExporterService(
//            $this,
//            new ParticipantExportableDataFormatter,
//            ucfirst(Event::where('ref', $request->event)->value('name')) . ' - Participants'
//        ))->download($request);
    }

    /**
     * @param  $request
     * @return Builder
     */
    private function getFilteredEventsQuery($request): Builder
    {
        $events = Event::with(['eventCategories', 'eventThirdParties:id,ref,event_id,external_id,partner_channel_id', 'eventThirdParties' => function ($query) {
            $query->with(['eventCategories:id,ref,name', 'eventCategories' => function ($query) {
                $query->withoutAppends();
            }, 'partnerChannel:id,partner_id,ref,name', 'partnerChannel.partner:id,ref,name,code'])
                ->whereNotNull('external_id')
                ->whereHas('partnerChannel', function ($query) {
                    $query->whereHas('partner', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        })->where('code', PredefinedPartnersEnum::LetsDoThis->value);
                    });
                })->has('eventCategories');
        }, 'image', 'gallery'])
            ->partnerEvent(Event::ACTIVE)
            ->estimated(Event::INACTIVE)
            ->state(EventStateEnum::Live)
            ->where('status', Event::ACTIVE)
            ->whereHas('eventCategories', function($query) use ($request) {
                $query->whereHas('site', function($query) {
                    $query->makingRequest();
                })->when(
                    $request->filled('year'),
                    fn ($query) => $query->whereYear('start_date', '=', $request->year)
                )->when(
                    $request->filled('month'),
                    fn ($query) => $query->whereMonth('start_date', '=', $request->month)
                )->when(
                    $request->filled('category'),
                    fn ($query) => $query->where('event_categories.ref', $request->category)
                )->where(function ($query) {
                    $query->whereNull('registration_deadline')
                        ->orWhere('registration_deadline', '>=', Carbon::now());
                })->where('end_date', '>=', Carbon::now());
            })->when(
                $request->filled('term'),
                fn ($query) => $query->where('name', 'LIKE', '%'.$request->term.'%')
            )->when(
                AccountType::isParticipant(),
                fn ($query) => $query->where('exclude_participants', Event::INACTIVE) // Don't return events that exclude participants
            )->when(
                AccountType::isEventManager(),
                fn ($query) => $query->whereHas('eventManagers', function ($query) {
                    $query->where('user_id', Auth::user()->id);
                })
            )->when(
                AccountType::isCharityOwnerOrCharityUser(),
                fn ($query) => $query->where(function($query) {
                    $query->where('charities', EventCharitiesEnum::All)
                        ->orWhere(function ($query) {
                            $query->where('charities', EventCharitiesEnum::Included)
                                ->whereHas('includedCharities', function($query) {
                                    $query->where('charity_id', Auth::user()->charityUser->charity_id);
                                });
                        })
                        ->orWhere(function ($query) {
                            $query->where('charities', EventCharitiesEnum::Excluded)
                                ->whereHas('excludedCharities', function($query) {
                                    $query->where('charity_id', Auth::user()->charityUser->charity_id);
                                });
                        });
                })
            );

            return $events->orderBy( // Default Ordering ASC
                EventEventCategory::select('start_date')
                    ->whereColumn('event_id', 'events.id')
                    ->orderBy('start_date')
                    ->limit(1)
            );
    }

    /**
     * @param  PartnerEventParticipantsListingQueryParamsRequest  $request
     * @return Builder
     */
    private function _getFilteredParticipantsQuery(PartnerEventParticipantsListingQueryParamsRequest $request): Builder
    {
        return Participant::with(['eventEventCategory.event', 'eventEventCategory.eventCategory', 'user.profile.participantProfile'/*, 'familyRegistrations'*/])
            ->appendsOnly([
                'latest_action',
                'formatted_status',
                'fee_type',
                'payment_status'
            ])->whereHas('eventEventCategory', function ($query) use ($request) {
                $query->whereHas('event', function ($query) use ($request) {
                    $query->where('ref', $request->event)
                        ->when(
                            AccountType::isEventManager(),
                            fn ($query) => $query->whereHas('eventManagers', function($query) {
                                $query->where('user_id', Auth::user()->id);
                            })
                        );
                })->whereHas('eventCategory', function ($query) {
                    $query->whereHas('site', function ($query) {
                        $query->makingRequest();
                    });
                });
            })->when(
                AccountType::isAccountManagerOrCharityOwnerOrCharityUser(),
                fn ($query) => $query->whereHas('charity', function ($query) {
                    $query->whereHas('users', function($query) {
                        $query->where('user_id', Auth::user()->id)
                            ->where(function($query) {
                                $query->where('type', CharityUserTypeEnum::Owner)
                                    ->orWhere('type', CharityUserTypeEnum::User)
                                    ->orWhere('type', CharityUserTypeEnum::Manager);
                            });
                    });
                })
            )->when(
                $request->filled('term'),
                fn ($query) => $query->whereHas('user', function ($query) use ($request) {
                    $query->where(function($query) use ($request) {
                        $query->where('first_name', 'like', '%'.$request->term.'%')
                            ->orWhere('last_name', 'like', '%'.$request->term.'%');
                    });
                })
            )->orderByDesc('created_at');
    }
}
