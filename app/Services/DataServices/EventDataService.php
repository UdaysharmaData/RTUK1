<?php

namespace App\Services\DataServices;

use App\Http\Helpers\FormatNumber;
use App\Jobs\ProcessDataServiceExport;
use App\Services\Reporting\Enums\EventStatisticsTypeEnum;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\EventStatistics;
use App\Services\Reporting\Traits\EventStatsTrait;
use App\Services\TimePeriodReferenceService;
use App\Traits\Response;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Modules\Event\Requests\EventListingQueryParamsRequest;

use App\Filters\FaqsFilter;
use App\Filters\MedalsFilter;
use App\Filters\DeletedFilter;
use App\Filters\EventsOrderByFilter;

use App\Enums\EventTypeEnum;
use App\Enums\EventStateEnum;
use App\Filters\DraftedFilter;
use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventEventCategory;
use App\Services\ExportManager\FileExporterService;
use App\Modules\Event\Requests\EventAllQueryParamsRequest;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\ExportManager\Formatters\EventExportableDataFormatter;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Notifications\ExportedListingDataAttachmentReadyNotification;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\DownloadTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Exports\CsvExporter;
use App\Traits\SiteTrait;

class EventDataService extends DataService implements DataServiceInterface
{
    use EventStatsTrait, Response, DownloadTrait,SiteTrait;

    /**
     * @var bool
     */
    private bool $loadRedirect = false;

    public function __construct($isBuilderRequired= true)
    {
        if($isBuilderRequired){
            $this->builder = Event::query();
        }
        $this->appendAnalyticsData = false;
    }

    /**
     * @param bool $value
     * @return EventDataService
     */
    public function setLoadRedirect(bool $value): static
    {
        $this->loadRedirect = $value;

        return $this;
    }

    /**
     * @param  mixed  $request
     * @return LengthAwarePaginator
     */
    public function all(mixed $request): LengthAwarePaginator
    {
        return $this->paginate($this->getFilteredAllQuery($request));
    }

    /**
     * @param  mixed  $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder
    {
        if($request instanceof EventListingQueryParamsRequest) {
            return $this->getFilteredEventsQuery($request);
        }
        if(in_array('export', $request->query()) ){
            return $this->getFilteredEventsQueryExport($request);
        }
 
    }

    /**
     * @param mixed $request
     * @param bool $sendAsAttachment
     * @return array|JsonResponse|BinaryFileResponse|StreamedResponse
     * @throws ExportableDataMissingException
     * @throws \Exception
     */
    public function download(mixed $request, bool $sendAsAttachment = false): array|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $request['export'] = true;
 
        $exportedList = $this->getExportList($request);
        $dataTemplate = app(EventExportableDataFormatter::class);
        $data = $dataTemplate->format($exportedList);
       
        // catch exception in controller to obtain the message and code
        if (empty($data)) throw new ExportableDataMissingException(sprintf('The %s were not found', 'events'), 406);
       
        $headers = ['Content-Type' => 'text/csv'];
        $fileName = Str::ucfirst('events') . '-' . date('Y-m-d_H-i-s') . '.csv';
        $path = config('app.csvs_path') . '/' . $fileName;
       
        Excel::store(
            new CsvExporter($data),
            config('app.csvs_path') . '/' . $fileName,
            config('filesystems.default'),
            \Maatwebsite\Excel\Excel::CSV,
            $headers
        );
 
        if ($sendAsAttachment) {
            return static::_download($path, true, null, true);
        }
 
        return static::_download($path, true);
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
                new EventExportableDataFormatter,
                'events'
            )),
            $request,
            $request->user(),
            $site,
        );

        return $this->success('The exported file will be sent to your email shortly.');
    }

 
 
    /**
     *param  mixed  $request
     * @return Builder
     */
    private function getFilteredEventsQueryExport(mixed $request): Builder
    {
        $events = Event::query()
            ->when($this->loadRedirect, fn ($query) => $query->with('redirect'))
            ->when(
                $request->export,
                fn ($query) => $query->select([
                    'id', 'ref', 'region_id', 'city_id', 'venue_id', 'name', 'slug', 'status', 'archived', 
                    'type', 'description', 'postcode', 'charity_checkout_integration', 'archived', 
                    'fundraising_emails', 'partner_event', 'postcode', 'country', 'website', 'deleted_at', 
                    'drafted_at'
                ])->with(['eventCategories:id,ref,name,color', 'image', 'address']),
                fn ($query) => $query->select([
                    'id', 'ref', 'name', 'slug', 'status', 'archived', 'deleted_at', 'drafted_at'
                ])->with(['eventCategories:id,ref,name,color', 'image', 'address:id,locationable_id,locationable_type,address'])
            )
            ->filterListBy(new DeletedFilter($request))
            ->filterListBy(new DraftedFilter($request))
            ->filterListBy(new EventsOrderByFilter($request))
            ->filterListBy(new FaqsFilter($request))
            ->filterListBy(new MedalsFilter($request))
            ->withCount('participants')
            ->when($request->has('country'), fn ($query) => $query->where('country', $request['country']))
            ->when($request->has('region'), fn ($query) => $query->whereHas('region', function ($query) use ($request) {
               // $query->where('ref', $request->region);
               $region_id = Region::where('ref',$request->region)->first()->id;
                $query->join('event_region_linking', 'events.id', '=', 'event_region_linking.event_id');
                $query->where('event_region_linking.region_id', $region_id);
            }))
            ->when($request->has('city'), fn ($query) => $query->whereHas('city', function ($query) use ($request) {
                $query->where('ref', $request->city);
            }))
            ->when($request->has('venue'), fn ($query) => $query->whereHas('venue', function ($query) use ($request) {
                $query->where('ref', $request->venue);
            }))
            ->when($request->has('experience'), fn ($query) => $query->whereHas('experiences', function ($query) use ($request) {
                $query->where('experiences.ref', $request->experience);
            }))
            ->when($request->has('type'), fn ($query) => $query->where('type', EventTypeEnum::from($request->type)))
            ->when($request->has('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->has('partner_event'), fn ($query) => $query->where('partner_event', $request->partner_event))
            ->when($request->has('term'), fn ($query) => $query->where('name', 'like', "%{$request->term}%"))
            ->when($request->has('state'), fn ($query) => $query->state(EventStateEnum::from($request->state)))
            ->when($request->has('ids'), fn ($query) => $query->whereIn('id', $request->ids))
            ->when($request->has('has_third_party_set_up') && $request->has_third_party_set_up, fn ($query) => $query->has('eventThirdParties'))
            ->when($request->has('has_third_party_set_up') && !$request->has_third_party_set_up, fn ($query) => $query->doesntHave('eventThirdParties'));
    
        $events = $events->whereHas('eventCategories', function ($query) use ($request) {
            $query->whereHas('site', function ($query) use ($request) {
                $query->whereHas('users', function ($query) use ($request) {
                    $query->where('user_id', $request->query('user_id'));
                })
                ->where('id', $request->query('site_id'));
            })
            ->when($request->has('year'), fn ($query) => $query->whereYear('start_date', '=', $request->year))
            ->when($request->has('month'), fn ($query) => $query->whereMonth('start_date', '=', $request->month))
            ->when($request->has('category'), fn ($query) => $query->where('event_categories.ref', $request->category));
        });
    
        $events = $events->when(!$request->has('order_by'), fn($query) => $query->orderByDesc(
            // Default Ordering
            EventEventCategory::select('start_date')
                ->whereColumn('event_id', 'events.id')
                ->orderByDesc('start_date')
                ->limit(1)
        ));
    
        return $events;
    }

    /**
     * @param  mixed  $request
     * @return LengthAwarePaginator|array
     */
    public function getPaginatedList(mixed $request): LengthAwarePaginator|array
    {
        $events = $this->paginate($this->getBuilderWithAnalytics($this->getFilteredQuery($request)))->through(function ($event) {
            $event->append('draft_url');

            return $event;
        });

        foreach ($events as $key => $event) {
            $events[$key]['amount'] = $event->amount();
        }

        return $events;
    }

    /**
     * @param  string  $event
     * @return \Illuminate\Database\Eloquent\Model|Builder
     */
    public function edit(string $event): Builder|\Illuminate\Database\Eloquent\Model
    {
        $model = $this->getBuilderWithAnalytics()
            ->with(['address:id,locationable_id,locationable_type,address,coordinates', 'city:id,ref,name,slug', 'eventCategories:id,ref,name,slug', 'eventThirdParties.eventCategories', 'eventThirdParties.partnerChannel:id,ref,name,code,partner_id', 'eventThirdParties.partnerChannel.partner:id,ref,name,code', 'eventManagers:id,user_id', 'eventManagers.user:id,ref,first_name,last_name', 'excludedCharities:id,ref,name,slug', 'faqs.faqDetails.uploads', 'image', 'includedCharities:id,ref,name,slug', 'gallery', 'meta', 'region:id,ref,name,slug', 'routeInfoMedia', 'socials', 'whatIsIncludedMedia', 'venue:id,ref,name,slug', 'sponsor:id,ref,name,slug', 'serie:id,ref,name,slug'])
            ->whereHas('eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            })->where('ref', $event)
            ->withDrafted()
            ->firstOrFail();

        $model->append('draft_url');

        return $this->modelWithAppendedAnalyticsAttribute($model);
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
     * @param  EventAllQueryParamsRequest                   $request
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    private function getFilteredAllQuery(EventAllQueryParamsRequest $request): \Illuminate\Database\Query\Builder|Builder
    {
        if ($request->filled('with') && $request->filled('with.value')) {
            switch($request->with['value']) {
                case 'categories':
                    $events = Event::queryAllWithCategories($request);
                    break;
                case 'third_parties':
                    $events = Event::queryAllWithThirdParties($request);
                    break;
                default:
                    $events = Event::queryAll($request);
                    break;
            }
        } else {
            $events = Event::queryAll($request);
        }

        return $events->orderBy('name');
    }

    /**
     * @param  EventListingQueryParamsRequest  $request
     * @return Builder
     */
    private function getFilteredEventsQuery(EventListingQueryParamsRequest $request): Builder
    {
        $events = Event::query()
            ->when($this->loadRedirect, fn ($query) => $query->with('redirect'))
            ->when(
                $request->export,
                fn ($query) => $query->select(['id', 'ref', 'region_id', 'city_id', 'venue_id', 'name', 'slug', 'status', 'archived', 'type', 'description', 'postcode', 'charity_checkout_integration', 'archived', 'fundraising_emails', 'partner_event', 'postcode', 'country', 'website', 'deleted_at', 'drafted_at'])
                    ->with(['eventCategories:id,ref,name,color', 'image', 'address']),
                fn ($query) => $query->select(['id', 'ref', 'name', 'slug', 'status', 'archived', 'deleted_at', 'drafted_at'])
                    ->with(['eventCategories:id,ref,name,color', 'image', 'address:id,locationable_id,locationable_type,address'])
            )->filterListBy(new DeletedFilter)
            ->filterListBy(new DraftedFilter)
            ->filterListBy(new EventsOrderByFilter)
            ->filterListBy(new FaqsFilter)
            ->filterListBy(new MedalsFilter)
            ->withCount('participants')
            ->when($request->filled('country'),
                fn ($query) => $query->where('country', $request->country)
            )
            ->when($request->filled('region'), fn ($query) => $query->whereHas('region', function ($query) use ($request) {
                $query->where('ref', $request->region);
            }))
            ->when($request->filled('city'),
                fn ($query) => $query->whereHas('city', function ($query) use ($request) {
                    $query->where('ref', $request->city);
                })
            )
            ->when($request->filled('venue'),
                fn ($query) => $query->whereHas('venue', function ($query) use ($request) {
                    $query->where('ref', $request->venue);
                })
            )
            ->when($request->filled('experience'),
                fn ($query) => $query->whereHas('experiences', function ($query) use ($request) {
                    $query->where('experiences.ref', $request->experience);
                })
            )
            ->when($request->filled('type'), fn ($query) => $query->where('type', EventTypeEnum::from($request->type)))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('partner_event'), fn ($query) => $query->where('partner_event', $request->partner_event))
            ->when($request->filled('term'), fn ($query) => $query->where('name', 'like', "%{$request->term}%"))
            ->when($request->filled('state'), fn ($query) => $query->state(EventStateEnum::from($request->state)))
            ->when($request->filled('ids'), fn ($query) => $query->whereIn('id', $request->ids))
            ->when($request->filled('has_third_party_set_up') && $request->has_third_party_set_up, fn ($query) => $query->has('eventThirdParties'))
            ->when($request->filled('has_third_party_set_up') && !$request->has_third_party_set_up, fn ($query) => $query->doesntHave('eventThirdParties'));

        $events = $events->whereHas('eventCategories', function ($query) use ($request) {
            $query->whereHas('site', function ($query) use ($request) {
                $query->hasAccess()
                    ->makingRequest();
            })->when($request->filled('year'), fn ($query) => $query->whereYear('start_date', '=', $request->year))
            ->when($request->filled('month'), fn ($query) => $query->whereMonth('start_date', '=', $request->month))
            ->when($request->filled('category'), fn ($query) => $query->where('event_categories.ref', $request->category));
        });

        $events = $events->when(! $request->filled('order_by'),
            fn($query) => $query->orderByDesc( // Default Ordering
                EventEventCategory::select('start_date')
                    ->whereColumn('event_id', 'events.id')
                    ->orderByDesc('start_date')
                    ->limit(1)
            )
        );

        return $events;
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
        return EventStatistics::generateStatsSummary($type, $year, $status, $category, $period);
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
        return EventStatistics::generateYearGraphData($type, $year, $status, $category, $period);
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param string|null $status
     * @param string|null $category
     * @param int|null $year
     * @param TimePeriodReferenceService|null $period
     * @return \array[][]
     */
    public static function eventsStatsData(?StatisticsEntityEnum $entity, ?string $status, ?string $category, ?int $year, ?TimePeriodReferenceService $period = null): array
    {
        return [
            'name' => EventStatisticsTypeEnum::Events->name,
            'total' => FormatNumber::format(self::eventsSummaryQuery($entity, $status, $category, $year, null, $period)->count()),
            'percent_change' => self::eventsSummaryPercentChange($entity, $status, $category, $year, null, $period),
            'type_param_value' => EventStatisticsTypeEnum::Events->value
        ];
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param string|null $status
     * @param string|null $category
     * @param int|null $year
     * @param int|null $month
     * @param TimePeriodReferenceService|null $period
     * @return Collection|\Illuminate\Support\Collection|array
     */
    public static function eventsStackedChartData(?StatisticsEntityEnum $entity = null, ?string $status = null, ?string $category = null, ?int $year = null, ?int $month = null, ?TimePeriodReferenceService $period = null): Collection|\Illuminate\Support\Collection|array
    {
        $categories = $status ? [EventStateEnum::tryFrom($status)] : EventStateEnum::cases();

        return collect($categories)->map(function ($state) use ($entity, $category, $year, $month, $period) {
            return [
                'name' => $state->name,
                'total' => self::eventsSummaryQuery($entity, $state->value, $category, $year, $month, $period)->count()
            ];
        });
    }
}
