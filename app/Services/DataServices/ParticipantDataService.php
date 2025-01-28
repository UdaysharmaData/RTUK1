<?php

namespace App\Services\DataServices;

use App\Http\Helpers\FormatNumber;
use App\Jobs\ProcessDataServiceExport;
use App\Services\Reporting\Enums\DashboardStatisticsTypeEnum;
use App\Services\Reporting\Enums\EventStatisticsTypeEnum;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\ParticipantStatistics;
use App\Services\Reporting\Traits\ParticipantStatsTrait;
use App\Services\TimePeriodReferenceService;
use App\Traits\PaginationTrait;
use App\Traits\Response;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Modules\Participant\Requests\ParticipantListingQueryParamsRequest;

use App\Filters\DeletedFilter;
use App\Filters\YearFilter;
use App\Filters\MonthFilter;
use App\Filters\PeriodFilter;
use App\Filters\ParticipantsOrderByFilter;
use App\Http\Helpers\AccountType;
use App\Modules\Participant\Models\Participant;
use App\Services\ExportManager\FileExporterService;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\ExportManager\Formatters\ParticipantExportableDataFormatter;
use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Enums\EventStateEnum;
use App\Enums\ParticipantStatusEnum;
use App\Enums\InvoiceItemStatusEnum;
use App\Enums\ParticipantAddedViaEnum;
use App\Enums\ParticipantPaymentStatusEnum;
use App\Enums\ParticipantWaiveEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Traits\SiteTrait;
use Illuminate\Http\Request;
class ParticipantDataService extends DataService implements DataServiceInterface
{
    use ParticipantStatsTrait, Response,SiteTrait;
    /**
     * @param  mixed  $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder
    {
      
        if($request instanceof ParticipantListingQueryParamsRequest) {
            return $this->getFilteredParticipantsQuery($request);
        }else{
           
            return $this->getFilteredParticipantsQueryExport($request);
        }
    }

    /**
     * param mixed $request
     * @return Builder
     */
    private function getFilteredParticipantsQueryExport(mixed $request): Builder
    {
        $participants = Participant::when(
            $request->export,
            fn ($query) => $query->select(['id', 'ref', 'user_id', 'event_event_category_id', 'charity_id', 'corporate_id', 'status', 'waive', 'waiver', 'preferred_heat_time', 'raced_before', 'estimated_finish_time', 'added_via', 'created_at', 'updated_at', 'deleted_at'])
                ->with([
                    'charity:id,charity_category_id,ref,name,slug',
                    'charity.charityCategory:id,ref,name',
                    'eventEventCategory.event:id,ref,name',
                    'eventEventCategory.eventCategory:id,ref,name',
                    'eventEventCategory.event.eventCustomFields',
                    'participantExtra:id,participant_id,ref,first_name,last_name',
                    'user:id,ref,email,first_name,last_name,phone',
                    'user.profile:id,user_id,gender,occupation,nationality,dob,postcode,state,city,state,address,country',
                    'user.profile.participantProfile:id,profile_id,tshirt_size,emergency_contact_name,emergency_contact_phone'
                ]),
            fn ($query) => $query->select(['id', 'ref', 'user_id', 'event_event_category_id', 'charity_id', 'corporate_id', 'status', 'waive', 'waiver', 'added_via', 'created_at', 'updated_at', 'deleted_at'])
                ->with([
                    'charity:id,charity_category_id,ref,name,slug',
                    'charity.charityCategory:id,ref,name',
                    'eventEventCategory.event:id,ref,name',
                    'eventEventCategory.eventCategory:id,ref,name',
                    'participantExtra:id,participant_id,ref,first_name,last_name',
                    'user:id,ref,email,first_name,last_name,phone',
                    'user.profile:id,user_id,gender,occupation,nationality,dob,postcode,state,city,state,address,country',
                ])
        )->appendsOnly([
            'latest_action',
            'formatted_status',
            'fee_type',
            'payment_status'
        ])->filterByAccess()
          ->filterListBy(new DeletedFilter($request))
          ->filterListBy(new ParticipantsOrderByFilter($request))
            ->filterListBy(new PeriodFilter($request))
            ->filterListBy(new YearFilter($request))
            ->filterListBy(new MonthFilter($request));
    
        $participants = $participants->whereHas('eventEventCategory', function ($query) use ($request) {
            $query->whereHas('event', function ($query) use ($request) {
                if ($request->has('event')) {
                    $query->where('ref', $request->event);
                }
    
                if ($request->has('state')) {
                    $query->state(EventStateEnum::from($request->state));
                }
            });
    
            $query->whereHas('eventCategory', function ($query) use ($request) {
                $query->whereHas('site', function ($query) use ($request) {
                    $query->whereHas('users', function ($query) use ($request) {
                        $query->where('user_id', $request->user_id);
                    })->where('id', $request->site_id);
                })->when($request->has('category'), fn ($query) => $query->where('ref', $request->category));
            });
        });
    
        $participants->when($request->has('status'), fn ($query) => $query->where('status', ParticipantStatusEnum::from($request->status)))
                     ->when($request->has('via'), fn ($query) => $query->where('added_via', ParticipantAddedViaEnum::from($request->via)))
                     ->when($request->has('waive'), fn ($query) => $query->where('waive', ParticipantWaiveEnum::from($request->waive)))
                     ->when($request->has('waiver'), fn ($query) => $query->where('waiver', ParticipantWaiverEnum::from($request->waiver)));
    
        if ($request->has('payment_status')) {
            $participants = $participants->when(
                $request->payment_status == ParticipantPaymentStatusEnum::Waived->value,
                fn ($query) => $query->whereNotNull('waive')
            )->when(
                $request->payment_status == ParticipantPaymentStatusEnum::Paid->value,
                fn ($query) => $query->whereHas('invoiceItem', function ($query) {
                    $query->where('status', InvoiceItemStatusEnum::Paid);
                })
            )->when(
                $request->payment_status == ParticipantPaymentStatusEnum::Unpaid->value,
                fn ($query) => $query->whereNot('waive', ParticipantWaiveEnum::Completely)
                    ->where(function ($query) {
                        $query->whereHas('invoiceItem', function ($query) {
                            $query->where('status', InvoiceItemStatusEnum::Unpaid);
                        })->orWhereDoesntHave('invoiceItem');
                    })
            )->when(
                $request->payment_status == ParticipantPaymentStatusEnum::Transferred->value,
                fn ($query) => $query->where('status', ParticipantPaymentStatusEnum::Transferred)
            )->when(
                $request->payment_status == ParticipantPaymentStatusEnum::Refunded->value,
                fn ($query) => $query->where('status', ParticipantPaymentStatusEnum::Refunded)
            );
        }
    
        if ($request->has('charity')) {
            $participants = $participants->whereHas('charity', function ($query) use ($request) {
                $query->where('ref', $request->charity);
            });
        }
    
        if ($request->has('term')) {
            $participants = $participants->where(function ($query) use ($request) {
                $query->whereHas('user', function ($query) use ($request) {
                    $query->where('first_name', 'like', '%' . $request->term . '%')
                        ->orWhere('last_name', 'like', '%' . $request->term . '%')
                        ->orWhere('email', 'like', '%' . $request->term . '%')
                        ->orWhereRaw('concat(first_name," ",last_name) LIKE ?', "%{$request->term}%");
                })->orWhereHas('participantExtra', function ($query) use ($request) {
                    $query->where('first_name', 'like', '%' . $request->term . '%')
                        ->orWhere('last_name', 'like', '%' . $request->term . '%')
                        ->orWhereRaw('concat(first_name," ",last_name) LIKE ?', "%{$request->term}%");
                });
            });
        }
    
        if ($request->has('gender')) {
            $participants = $participants->whereHas('user', function ($query) use ($request) {
                $query->whereHas('profile', function ($query) use ($request) {
                    $query->where('gender', $request->gender);
                });
            });
        }
    
        if ($request->has('tshirt_size')) {
            $participants = $participants->whereHas('user', function ($query) use ($request) {
                $query->whereHas('profile', function ($query) use ($request) {
                    $query->whereHas('participantProfile', function ($query) use ($request) {
                        $query->where('tshirt_size', $request->tshirt_size);
                    });
                });
            });
        }
    
        $participants = $participants->when(!$request->has('order_by'), // Default Ordering
            fn ($query) => $query->orderByDesc('created_at')
        );
 
        return $participants;
    }

    /**
     * @param  mixed  $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedList(mixed $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->paginate($this->getFilteredQuery($request));
    }

    /**
     * @param  string       $participant
     * @return Participant
     */
    public function edit(string $participant): Participant
    {
        $_participant = Participant::with([
            'charity:id,ref,name,slug',
            'eventEventCategory.event' => function ($query) {
                $query->withoutAppends();
            },
            'eventEventCategory.event:id,ref,name,slug',
            'eventEventCategory.event.eventCustomFields',
            'eventEventCategory.eventCategory:id,ref,name,slug',
            'eventEventCategory.event.eventCategories',
            'eventPage',
            'invoiceItem.invoice.upload',
            'participantActions' => function ($query) {
                $query->with(['user:id,ref,first_name,last_name', 'role:id,ref,name', 'participant' => function ($q1) {
                        $q1->select(['id', 'user_id', 'event_event_category_id']);
                    }, 'participant.user:id,ref,first_name,last_name'])
                    ->appendsOnly([
                        'description'
                    ])->orderByDesc('created_at');
                },
            'participantCustomFields.eventCustomField',
            'participantExtra:id,ref,participant_id,first_name,last_name',
            'user:id,ref,email,first_name,last_name,phone',
            'user.profile.participantProfile'
        ])->appendsOnly([
                'latest_action',
                'formatted_status',
                'fee_type',
                'payment_status',
            ])->where('ref', $participant)
            ->filterByAccess();

        $_participant = $_participant->whereHas('eventEventCategory.eventCategory', function ($query) {
            $query->whereHas('site', function ($q) {
                $q->hasAccess()
                    ->makingRequest();
            });
        });

        if (AccountType::isAdmin()) {
            $_participant = $_participant->withTrashed();
        }

        $_participant = $_participant->firstOrFail();

        $_participant->eventEventCategory['participant_registration_fee'] = $_participant->eventEventCategory->userRegistrationFee($_participant->user); // Update the registration fee to that for the user

        return $_participant;
    }

    /**
     * @param  mixed  $request
     * @return \Illuminate\Database\Eloquent\Collection|Builder
     */
    public function getExportList(mixed $request): Builder|\Illuminate\Database\Eloquent\Collection
    {
        return $this->getFilteredQuery($request)->get();
    }

    /**
     * @param mixed $request
     * @return array|JsonResponse|BinaryFileResponse|StreamedResponse
     */
    public function downloadCsv(mixed $request): array|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $site = static::getSite();
        ProcessDataServiceExport::dispatch(
            (new FileExporterService(
                $this,
                new ParticipantExportableDataFormatter,
                'participants'
            )),
            $request,
            $request->user(),
            $site
        );

        return $this->success('The exported file will be sent to your email shortly.');
    }

    /**
     * @param  ParticipantListingQueryParamsRequest  $request
     * @return Builder
     */
    private function getFilteredParticipantsQuery(ParticipantListingQueryParamsRequest $request): Builder
    {
        $participants = Participant::when(
            $request->export,
            fn ($query) => $query->select(['id', 'ref', 'user_id', 'event_event_category_id', 'charity_id', 'corporate_id', 'status', 'waive', 'waiver', 'preferred_heat_time', 'raced_before', 'estimated_finish_time', 'added_via', 'created_at', 'updated_at', 'deleted_at'])
                ->with([
                    'charity:id,charity_category_id,ref,name,slug',
                    'charity.charityCategory:id,ref,name',
                    'eventEventCategory.event:id,ref,name',
                    'eventEventCategory.eventCategory:id,ref,name',
                    'eventEventCategory.event.eventCustomFields',
                    /*'familyRegistrations', */
                    'participantExtra:id,participant_id,ref,first_name,last_name',
                    'user:id,ref,email,first_name,last_name,phone',
                    'user.profile:id,user_id,gender,occupation,nationality,dob,postcode,state,city,state,address,country',
                    'user.profile.participantProfile:id,profile_id,tshirt_size,emergency_contact_name,emergency_contact_phone'
                ]),
            fn ($query) => $query->select(['id', 'ref', 'user_id', 'event_event_category_id', 'charity_id', 'corporate_id', 'status', 'waive', 'waiver', 'added_via', 'created_at', 'updated_at', 'deleted_at'])
                ->with([
                    'charity:id,charity_category_id,ref,name,slug',
                    'charity.charityCategory:id,ref,name',
                    'eventEventCategory.event:id,ref,name',
                    'eventEventCategory.eventCategory:id,ref,name',
                    /*'familyRegistrations', */
                    'participantExtra:id,participant_id,ref,first_name,last_name',
                    'user:id,ref,email,first_name,last_name,phone',
                    'user.profile:id,user_id,gender,occupation,nationality,dob,postcode,state,city,state,address,country',
                ])
            )->appendsOnly([
                'latest_action',
                'formatted_status',
                'fee_type',
                'payment_status'
            ])->filterByAccess()
            ->filterListBy(new DeletedFilter)
            ->filterListBy(new ParticipantsOrderByFilter)
            ->filterListBy(new PeriodFilter)
            ->filterListBy(new YearFilter)
            ->filterListBy(new MonthFilter);

        $participants = $participants->whereHas('eventEventCategory', function ($query) use ($request) {
            $query->whereHas('event', function ($query) use ($request) {
                if ($request->filled('event')) {
                    $query->where('ref', $request->event);
                }

                if ($request->filled('state')) {
                    $query = $query->state(EventStateEnum::from($request->state));
                }
            });

            $query->whereHas('eventCategory', function ($query) use ($request) {
                $query->whereHas('site', function ($q) use ($request) {
                    $q->hasAccess()
                        ->makingRequest();
                })->when($request->filled('category'), fn ($query) => $query->where('ref', $request->category));
            });
        })->when($request->filled('status'), fn ($query) => $query->where('status', ParticipantStatusEnum::from($request->status)))
        ->when($request->filled('via'), fn ($query) => $query->where('added_via', ParticipantAddedViaEnum::from($request->via)))
        ->when($request->filled('waive'), fn ($query) => $query->where('waive', ParticipantWaiveEnum::from($request->waive)))
        ->when($request->filled('waiver'), fn ($query) => $query->where('waiver', ParticipantWaiverEnum::from($request->waiver)));

        if ($request->filled('payment_status')) {
            $participants = $participants->when(
                $request->payment_status == ParticipantPaymentStatusEnum::Waived->value,
                fn ($query) => $query->whereNotNull('waive')
            )->when(
                $request->payment_status == ParticipantPaymentStatusEnum::Paid->value,
                fn ($query) => $query->whereHas('invoiceItem', function ($query) {
                    $query->where('status', InvoiceItemStatusEnum::Paid);
                })
            )->when(
                $request->payment_status == ParticipantPaymentStatusEnum::Unpaid->value,
                fn ($query) => $query->whereNot('waive', ParticipantWaiveEnum::Completely)
                    ->where(function ($query) {
                        $query->whereHas('invoiceItem', function ($query) {
                            $query->where('status', InvoiceItemStatusEnum::Unpaid);
                        })->orWhereDoesntHave('invoiceItem');
                    })
            )->when(
                $request->payment_status == ParticipantPaymentStatusEnum::Transferred->value,
                fn ($query) => $query->where('status', ParticipantPaymentStatusEnum::Transferred)
            )->when(
                $request->payment_status == ParticipantPaymentStatusEnum::Refunded->value,
                fn ($query) => $query->where('status', ParticipantPaymentStatusEnum::Refunded)
            );
        }

        if ($request->filled('charity')) {
            $participants = $participants->whereHas('charity', function ($query) use ($request) {
                $query->where('ref', $request->charity);
            });
        }

        if ($request->filled('term')) {
            $participants = $participants->where(function ($query) use ($request) {
                $query->whereHas('user', function ($query) use ($request) {
                    $query->where('first_name', 'like', '%'. $request->term . '%')
                        ->orWhere('last_name', 'like', '%'. $request->term . '%')
                        ->orWhere('email', 'like', '%'. $request->term . '%')
                        ->orWhereRaw('concat(first_name," ",last_name) LIKE ?', "%{$request->term}%");
                })->orWhereHas('participantExtra', function ($query) use ($request) {
                    $query->where('first_name', 'like', '%'. $request->term . '%')
                        ->orWhere('last_name', 'like', '%'. $request->term . '%')
                        ->orWhereRaw('concat(first_name," ",last_name) LIKE ?', "%{$request->term}%");
                });
            });
        }

        if ($request->filled('gender')) {
            $participants = $participants->whereHas('user', function ($query) use ($request) {
                $query->whereHas('profile', function ($query) use ($request) {
                    $query->where('gender', $request->gender);
                });
            });
        }

        if ($request->filled('tshirt_size')) {
            $participants = $participants->whereHas('user', function ($query) use ($request) {
                $query->whereHas('profile', function ($query) use ($request) {
                    $query->whereHas('participantProfile', function ($query) use ($request) {
                        $query->where('tshirt_size', $request->tshirt_size);
                    });
                });
            });
        }

        $participants = $participants->when(! $request->filled('order_by'), // Default Ordering
            fn ($query) => $query->orderByDesc('created_at')
        );

        return $participants;
    }

    /**
     * @param $type
     * @param $year
     * @param $status
     * @param $category
     * @param $period
     * @return array
     */
    public function generateStatsSummary($type, $year, $status, $category, $period): array
    {
        return ParticipantStatistics::generateStatsSummary($type, $year, $status, $category, $period);
    }

    /**
     * @param $type
     * @param $year
     * @param $status
     * @param $category
     * @param $period
     * @return array
     */
    public function generateYearGraphData($type, $year, $status, $category, $period): array
    {
        return ParticipantStatistics::generateYearGraphData($type, $year, $status, $category, $period);
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param string|null $status
     * @param string|null $category
     * @param int|null $month
     * @param TimePeriodReferenceService|null $period
     * @return \array[][]
     */
    public static function participantsStatsData(?StatisticsEntityEnum $entity, ?int $year, ?string $status, ?string $category, ?int $month, ?TimePeriodReferenceService $period = null): array
    {
        return [
            'name' => EventStatisticsTypeEnum::Participants->name,
            'total' => FormatNumber::format(self::participantsSummaryQuery($entity, $year, $status, $category, $month, null, $period)->count()),
            'percent_change' => self::participantsSummaryPercentChange($entity, $year, $status, $category, $month, null, $period),
            'type_param_value' => EventStatisticsTypeEnum::Participants->value
        ];
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param string|null $status
     * @param string|null $category
     * @param int|null $month
     * @param int|null $userId
     * @param TimePeriodReferenceService|null $period
     * @return Collection|\Illuminate\Support\Collection|array
     */
    public static function participantsStackedAreaChartData(
        ?StatisticsEntityEnum $entity = null,
        ?int $year = null,
        ?string $status = null,
        ?string $category = null,
        ?int $month = null,
        ?int $userId = null,
        ?TimePeriodReferenceService $period = null
    ): Collection|\Illuminate\Support\Collection|array
    {
        return Participant::query()
            ->whereHas('eventEventCategory', function ($query) {
                $query->whereHas('eventCategory', function ($query) {
                    $query->whereHas('site', function ($query) {
                        if (AccountType::isAdmin()) {
                            $query->hasAccess();
                        }
                        $query->makingRequest();
                    });
                });
            })->select(['status'])
            ->when($status, fn($query) => $query->where('status', '=', $status))
            ->distinct()
            ->get()
            ->map(function ($participant) use ($entity, $year, $category, $month, $userId, $period) {
                $item = [];
                $item['name'] = $participant->status->name;
                $item['total'] = self::participantsSummaryQuery($entity, $year, $participant->status->value, $category, $month, $userId, $period)->count();
                return $item;
            });
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param string|null $status
     * @param string|null $category
     * @param int|null $month
     * @param int|null $userId
     * @param TimePeriodReferenceService|null $period
     * @return \array[][]
     */
    public static function entriesStatsData(?StatisticsEntityEnum $entity, ?int $year, ?string $status, ?string $category, ?int $month, ?int $userId = null, ?TimePeriodReferenceService $period = null): array
    {
        return [
            'name' => DashboardStatisticsTypeEnum::Entries->name,
            'total' => FormatNumber::format(self::participantsSummaryQuery($entity, $year, $status, $category, $month, $userId, $period)->count()),
            'percent_change' => self::participantsSummaryPercentChange($entity, $year, $status, $category, $month, $userId, $period),
            'type_param_value' => DashboardStatisticsTypeEnum::Entries->value
        ];
    }
}
