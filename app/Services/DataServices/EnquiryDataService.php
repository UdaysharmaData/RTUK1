<?php

namespace App\Services\DataServices;

use App\Enums\EnquiryStatusEnum;
use App\Jobs\ProcessDataServiceExport;
use App\Services\ExportManager\Formatters\ParticipantExportableDataFormatter;
use App\Services\Reporting\EnquiryStatistics;
use App\Traits\PaginationTrait;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Modules\Enquiry\Requests\EnquiryListingQueryParamsRequest;

use App\Filters\YearFilter;
use App\Filters\MonthFilter;
use App\Filters\PeriodFilter;
use App\Filters\DeletedFilter;
use App\Filters\EnquiriesOrderByFilter;

use App\Modules\Enquiry\Models\Enquiry;

use App\Services\ExportManager\FileExporterService;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\ExportManager\Formatters\EnquiryExportableDataFormatter;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Enums\EnquiryActionEnum;
use App\Traits\SiteTrait;

class EnquiryDataService extends DataService implements DataServiceInterface
{
    use Response,SiteTrait;

    /**
     * @param  mixed  $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder
    {

        if($request instanceof EnquiryListingQueryParamsRequest) {
            return $this->getFilteredEnquiriesQuery($request);
        }else{
            return $this->getFilteredEnquiriesQueryExport($request);
        }
    }

    /**
     * param mixed $request
     * @return Builder
     *
     * @description
     * This function takes a request and returns a filtered query of enquiries.
     * The query is filtered by the following criteria:
     * - The site where the enquiry was made
     * - The status of the enquiry
     * - The action taken on the enquiry
     * - The charity that the enquiry was made for
     * - The event that the enquiry was made for
     * - The term that the enquiry contains
     * - Whether the enquiry was contacted or not
     * - Whether the enquiry was converted or not
     * - The order by clause (default is descending order by created at)
     *
     * @return Builder
     */
    private function getFilteredEnquiriesQueryExport(mixed $request): Builder
    {
        /**
         * @var Builder $enquiries
         */
        $enquiries = Enquiry::with([
            'charity:id,ref,name,slug,charity_category_id',
            'charity.charityCategory:id,ref,name,slug',
            'event' => function ($query) {
                $query->withoutAppends()->select('id', 'ref', 'slug', 'name');
            },
            'event.eventCategories' => function ($query) {
                $query->orderByDesc('end_date');
            },
        ])->whereHas('site', function ($query) use ($request) {
            $query->where('id', $request->site_id);
        })->filterByAccess()
            ->filterListBy(new DeletedFilter($request))
            ->filterListBy(new EnquiriesOrderByFilter($request))
            ->filterListBy(new PeriodFilter($request))
            ->filterListBy(new YearFilter($request))
            ->filterListBy(new MonthFilter($request))
            ->when($request->action, function ($query) use ($request) {
                $query->where('action', EnquiryActionEnum::from($request->action));
            });

        // Filter by charity ref
        if ($request->has('charity')) {
            $enquiries = $enquiries->whereHas('charity', function ($query) use ($request) {
                $query->where('ref', $request->charity);
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $enquiries = $enquiries->status(EnquiryStatusEnum::tryFrom($request->status));
        }

        // Filter by term
        if ($request->has('term')) {
            $enquiries = $enquiries->where(function ($query) use ($request) {
                $query->where('first_name', 'like', '%' . $request->term . '%')
                    ->orWhere('last_name', 'like', '%' . $request->term . '%')
                    ->orWhere('email', 'like', '%' . $request->term . '%')
                    ->orWhere('phone', 'like', '%' . $request->term . '%');
            });
        }

        // Filter by event ref
        if ($request->has('event')) {
            $enquiries = $enquiries->whereHas('event', function ($query) use ($request) {
                $query->where('ref', $request->event);
            });
        }

        // Filter by whether the enquiry was contacted or not
        if ($request->has('contacted')) {
            $enquiries->where('contacted', $request->contacted);
        }

        // Filter by whether the enquiry was converted or not
        if ($request->has('converted')) {
            $enquiries->where('converted', $request->converted);
        }

        // Default ordering by created at
        $enquiries = $enquiries->when(
            !$request->has('order_by'),
            fn ($query) => $query->orderByDesc('created_at')
        );

        return $enquiries;
    }


    /**
     * @param  mixed  $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedList(mixed $request): LengthAwarePaginator
    {
        return $this->paginate($this->getFilteredQuery($request));
    }

    /**
     * @param  string $ref
     * @return Enquiry
     */
    public function edit(string $ref): Enquiry
    {
        return Enquiry::with([
            'charity:id,ref,name,slug',
            'event' => function ($query) {
                $query->withoutAppends()->withOnly([])->select('id', 'ref', 'slug', 'name');
            },
            'eventCategory',
            'externalEnquiry',
            'site',
            'participant' => function ($query) {
                $query->AppendsOnly([
                    'formatted_status',
                    'payment_status'
                    ])->withOnly([
                    'eventEventCategory.event' => function ($query) {
                        $query->withoutAppends()->withOnly([])->select('id', 'ref', 'name');
                    },
                    'eventEventCategory.eventCategory',
                    'user',
                    'charity:id,ref,name,slug'
                ]);
            },
            'user.charityUser.charity:id,ref,name,slug'
        ])->whereHas('site', function ($query) {
            $query->makingRequest();
        })->where('ref', $ref)
            ->filterByAccess()
            ->firstOrFail();
    }

    /**
     * @param  mixed  $request
     * @return \Illuminate\Database\Eloquent\Collection|Builder
     */
    public function getExportList(mixed $request): Builder|\Illuminate\Database\Eloquent\Collection
    {
        return $this->getFilteredQuery($request)->with('eventCategory')->get();
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
                new EnquiryExportableDataFormatter,
                'enquiries'
            )),
            $request,
            $request->user(),
            $site,
        );

        return $this->success('The exported file will be sent to your email shortly.');
    }

    /**
     * @param  EnquiryListingQueryParamsRequest  $request
     * @return Builder
     */
    private function getFilteredEnquiriesQuery(EnquiryListingQueryParamsRequest $request): Builder
    {
        $enquiries = Enquiry::with([
            'charity:id,ref,name,slug,charity_category_id',
            'charity.charityCategory:id,ref,name,slug',
            'event' => function ($query) {
                $query->withoutAppends()->select('id', 'ref', 'slug', 'name');
            },
            'event.eventCategories' => function ($query) {
                $query->orderByDesc('end_date');
            },
        ])->whereHas('site', function ($query) use ($request) {
            $query->makingRequest();
        })->filterByAccess()
            ->filterListBy(new DeletedFilter)
            ->filterListBy(new EnquiriesOrderByFilter)
            ->filterListBy(new PeriodFilter)
            ->filterListBy(new YearFilter)
            ->filterListBy(new MonthFilter)
            ->when($request->action, function ($query) use ($request) {
                $query->where('action', EnquiryActionEnum::from($request->action));
            });

        if ($request->filled('charity')) {
            $enquiries = $enquiries->whereHas('charity', function ($query) use ($request) {
                $query->where('ref', $request->charity);
            });
        }

        if ($request->filled('status')) {
            $enquiries = $enquiries->status(EnquiryStatusEnum::tryFrom($request->status));
        }

        if ($request->filled('term')) {
            $enquiries = $enquiries->where(function ($query) use ($request) {
                $query->where('first_name', 'like', '%' . $request->term . '%')
                    ->orWhere('last_name', 'like', '%' . $request->term . '%')
                    ->orWhere('email', 'like', '%' . $request->term . '%')
                    ->orWhere('phone', 'like', '%' . $request->term . '%');
            });
        }

        if ($request->filled('event')) {
            $enquiries = $enquiries->whereHas('event', function ($query) use ($request) {
                $query->where('ref', $request->event);
            });
        }

        if ($request->filled('contacted')) {
            $enquiries->where('contacted', $request->contacted);
        }

        if ($request->filled('converted')) {
            $enquiries->where('converted', $request->converted);
        }

        $enquiries = $enquiries->when(
            !$request->filled('order_by'), // Default Ordering
            fn ($query) => $query->orderByDesc('created_at')
        );

        return $enquiries;
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
        return EnquiryStatistics::generateStatsSummary($type, $year, $status, $category, $period);
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
        return EnquiryStatistics::generateYearGraphData($type, $year, $status, $category, $period);
    }
}
