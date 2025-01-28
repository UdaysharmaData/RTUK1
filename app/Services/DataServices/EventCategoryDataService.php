<?php

namespace App\Services\DataServices;

use App\Enums\EventCategoryVisibilityEnum;
use App\Jobs\ProcessDataServiceExport;
use App\Services\Reporting\EventCategoryStatistics;
use App\Traits\Response;
use Auth;
use Carbon\Carbon;
use App\Http\Helpers\AccountType;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Modules\Event\Requests\EventCategoryAllQueryParamsRequest;
use App\Modules\Event\Requests\EventCategoryListingQueryParamsRequest;
use App\Filters\FaqsFilter;
use App\Filters\MedalsFilter;
use App\Filters\DeletedFilter;
use App\Filters\DraftedFilter;
use App\Filters\EventCategoriesOrderByFilter;

use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventCategory;
use App\Services\ConfigurableEventPropertyService;
use App\Services\ExportManager\FileExporterService;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\ExportManager\Formatters\EventCategoryExportableDataFormatter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventCategoryDataService extends DataService implements DataServiceInterface
{
    use Response;

    /**
     * @var bool
     */
    private bool $loadRedirect = false;

    public function __construct()
    {
        $this->builder = EventCategory::query();
        $this->appendAnalyticsData = false;
    }

    /**
     * @param bool $value
     * @return EventCategoryDataService
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
        return $this->getFilteredEventCategoriesQuery($request);
    }

    /**
     * @param  mixed  $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedList(mixed $request): LengthAwarePaginator
    {
        $data = $this->paginate($this->getBuilderWithAnalytics($this->getFilteredQuery($request)))->through(function ($item) {
            $item->append('draft_url');

            return $item;
        });

        return $data;
    }

    /**
     * @param  string         $category
     * @return EventCategory
     */
    public function _show(string $category): EventCategory
    {
        return (new ConfigurableEventPropertyService(EventCategory::query(), []))->_show($category);
    }

    /**
     * @param  string         $category
     * @return \Illuminate\Database\Eloquent\Model|Builder
     */
    public function edit(string $category): Builder|\Illuminate\Database\Eloquent\Model
    {
        $model = $this->getBuilderWithAnalytics()
            ->with(['faqs', 'meta', 'site' => function ($query) {
            $query->hasAccess()
                ->makingRequest();
        }, 'image', 'gallery'])
            ->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->where('ref', $category)
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
     * @param mixed $request
     * @return array|JsonResponse|BinaryFileResponse|StreamedResponse
     */
    public function downloadCsv(mixed $request): array|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        ProcessDataServiceExport::dispatch(
            (new FileExporterService(
                $this,
                new EventCategoryExportableDataFormatter,
                'Event Categories'
            )),
            json_encode($request),
            $request->user()
        );

        return $this->success('The exported file will be sent to your email shortly.');

//        return (new FileExporterService(
//            $this,
//            new EventCategoryExportableDataFormatter,
//            'Event Categories'
//        ))->download($request);
    }

    /**
     * @param  EventCategoryAllQueryParamsRequest          $request
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    private function getFilteredAllQuery(EventCategoryAllQueryParamsRequest $request): \Illuminate\Database\Query\Builder|Builder
    {
        $categories = EventCategory::select('id', 'ref', 'name', 'slug', 'site_id')
            ->whereHas('site', function ($query) {
                $query->makingRequest();
            });

        if ($request->filled('term')) {
            $categories = $categories->where('name', 'like', "%{$request->term}%");
        }

        if (AccountType::isParticipant()) {
            $categories = $categories->whereHas('events', function ($query) {
                // $query->state(EventStateEnum::Live);
                $query->estimated(Event::INACTIVE);
                $query->archived(Event::INACTIVE);
                // $query->partnerEvent(Event::ACTIVE);
                $query->where('status', Event::ACTIVE);
                $query->where('end_date', '>', Carbon::now());
            })->when($request->filled('for') && $request->for == 'entries', fn ($query) => $query->whereHas('participants', function ($query) {
                $query->where('user_id', Auth::user()->id);
            }));
        }

        if ($request->filled('with_setting_custom_fields') && $request->with_setting_custom_fields) {
            $categories = $categories->with(['site.setting.settingCustomFields' => function ($query) {
                $query->whereIn('key', ['classic_membership_default_places', 'premium_membership_default_places', 'two_year_membership_default_places', 'partner_membership_default_places']);
            }]);
        }

        return $categories;
    }

    /**
     * @param  EventCategoryListingQueryParamsRequest  $request
     * @return Builder
     */
    private function getFilteredEventCategoriesQuery(EventCategoryListingQueryParamsRequest $request): Builder
    {
        return EventCategory::query()
            ->when($this->loadRedirect, fn ($query) => $query->with('redirect'))
            ->with(['site' => function ($query) {
                $query->makingRequest();
            }, 'image', 'gallery'])
                ->withCount('events')
                ->filterListBy(new FaqsFilter)
                ->filterListBy(new DraftedFilter)
                ->filterListBy(new DeletedFilter)
                ->filterListBy(new EventCategoriesOrderByFilter)
                ->filterListBy(new MedalsFilter)
                ->when($request->filled('visibility'), fn($query) => $query->where('visibility', EventCategoryVisibilityEnum::from($request->visibility)))
                ->whereHas('site', function ($query) {
                    $query->makingRequest();
                })->when($request->filled('term'), fn ($query) => $query->where('name', 'like', '%'.$request->term.'%'))
                ->when(! $request->filled('order_by'), // Default Ordering
                    fn($query) => $query->orderBy('name')
                );
    }

    /**
     * @param $year
     * @param $status
     * @param $period
     * @return array
     */
    public function generateStatsSummary($year, $status, $period): array
    {
        return EventCategoryStatistics::generateStatsSummary($year, $status, $period);
    }
}
