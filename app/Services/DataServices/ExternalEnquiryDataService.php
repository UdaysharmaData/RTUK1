<?php

namespace App\Services\DataServices;

use App\Jobs\ProcessDataServiceExport;
use App\Services\Reporting\ExternalEnquiryStatistics;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use App\Enums\ExternalEnquiryStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Modules\Enquiry\Requests\ExternalEnquiryListingQueryParamsRequest;

use App\Filters\YearFilter;
use App\Filters\MonthFilter;
use App\Filters\PeriodFilter;
use App\Filters\DeletedFilter;
use App\Filters\ExternalEnquiriesOrderByFilter;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Services\ExportManager\FileExporterService;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\ExportManager\Formatters\ExternalEnquiryExportableDataFormatter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Traits\SiteTrait;

class ExternalEnquiryDataService extends DataService implements DataServiceInterface
{
    use Response,SiteTrait;

    /**
     * @param  mixed  $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder
    { 
       
        if($request instanceof ExternalEnquiryListingQueryParamsRequest) {
            return $this->getFilteredEnquiriesQuery($request);
        }else{
            return $this->getFilteredEnquiriesQueryExport($request);
        }
    }

    /**
     * param mixed $request
     * @return Builder
     */
    private function getFilteredEnquiriesQueryExport(mixed $request): Builder
    {
        $enquiries = ExternalEnquiry::with([
            'charity:id,ref,name,slug',
            'charity.charityCategory',
            'event' => function ($query) {
                $query->withOnly([])->withoutAppends()->select('id', 'ref', 'slug', 'name');
            },
            'partnerChannel',
            'eventCategoryEventThirdParty.eventCategory'
        ])->whereHas('site', function ($query) use ($request) {
            $query->where('id', $request->query('site_id'));
        })->filterByAccess()
            ->filterListBy(new DeletedFilter($request))
            ->filterListBy(new ExternalEnquiriesOrderByFilter($request))
            ->filterListBy(new PeriodFilter($request))
            ->filterListBy(new YearFilter($request))
            ->filterListBy(new MonthFilter($request));
    
        if ($request->has('charity')) {
            $enquiries = $enquiries->whereHas('charity', function ($query) use ($request) {
                $query->where('ref', $request->charity);
            });
        }
    
        if ($request->has('partner')) {
            $enquiries = $enquiries->whereHas('partnerChannel', function ($query) use ($request) {
                $query->whereHas('partner', function ($query) use ($request) {
                    $query->where('ref', $request->partner);
                });
            });
        }
    
        if ($request->has('channel')) {
            $enquiries = $enquiries->whereHas('partnerChannel', function ($query) use ($request) {
                $query->where('ref', $request->channel);
            });
        }
    
        if ($request->has('status')) {
            $enquiries = $enquiries->status(ExternalEnquiryStatusEnum::tryFrom($request->status));
        }
    
        if ($request->has('term')) {
            $enquiries = $enquiries->where(function ($query) use ($request) {
                $query->where('first_name', 'like', '%' . $request->term . '%')
                    ->orWhere('last_name', 'like', '%' . $request->term . '%')
                    ->orWhere('email', 'like', '%' . $request->term . '%')
                    ->orWhere('phone', 'like', '%' . $request->term . '%');
            });
        }
    
        if ($request->has('event')) {
            $enquiries = $enquiries->whereHas('event', function ($query) use ($request) {
                $query->where('ref', $request->event);
            });
        }
    
        if ($request->has('contacted')) {
            $enquiries->where('contacted', $request->contacted);
        }
    
        if ($request->has('converted')) {
            $enquiries->where('converted', $request->converted);
        }
    
        $enquiries = $enquiries->when(!$request->has('order_by'), // Default Ordering
            fn($query) => $query->orderByDesc('created_at')
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
     * @return ExternalEnquiry
     */
    public function edit(string $ref): ExternalEnquiry
    {
        return ExternalEnquiry::with([
            'charity:id,ref,name', 'event:id,ref,slug,name', 'event.eventCategories', 'event.eventThirdParties' => function ($query) {
                $query->with(['eventCategories', 'partnerChannel'])
                    ->whereNotNull('external_id')
                    ->whereHas('partnerChannel', function ($query) {
                        $query->whereHas('partner', function ($query) {
                            $query->whereHas('site', function ($query) {
                                $query->makingRequest();
                            });
                        });
                    });
            }, 'eventCategoryEventThirdParty.eventCategory', 'eventCategoryEventThirdParty.eventThirdParty', 'site',
            'participant' => function ($query) {
                $query->AppendsOnly([
                    'formatted_status',
                    'payment_status'
                ])->withOnly([
                    'eventEventCategory.event' => function ($query) {
                        $query->withoutAppends()->withOnly([])->select('id', 'ref', 'slug', 'name');
                    },
                    'eventEventCategory.eventCategory',
                    'user',
                    'charity:id,ref,name'
                ]);
            },
            'partnerChannel',
            'user.charityUser.charity'
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
                new ExternalEnquiryExportableDataFormatter,
                'external-enquiries'
            )),
            $request,
            $request->user(),
            $site,
        );

        return $this->success('The exported file will be sent to your email shortly.');
    }

    /**
     * @param  ExternalEnquiryListingQueryParamsRequest  $request
     * @return Builder
     */
    private function getFilteredEnquiriesQuery(ExternalEnquiryListingQueryParamsRequest $request): Builder
    {
        $enquiries = ExternalEnquiry::with([
            'charity:id,ref,name,slug',
            'charity.charityCategory',
            'event' => function ($query) {
                $query->withOnly([])->withoutAppends()->select('id', 'ref', 'slug', 'name');
            },
            'partnerChannel',
            'eventCategoryEventThirdParty.eventCategory'
        ])->whereHas('site', function ($query) use ($request) {
            $query->makingRequest();
        })->filterByAccess()
            ->filterListBy(new DeletedFilter)
            ->filterListBy(new ExternalEnquiriesOrderByFilter)
            ->filterListBy(new PeriodFilter)
            ->filterListBy(new YearFilter)
            ->filterListBy(new MonthFilter);

        if ($request->filled('charity')) {
            $enquiries = $enquiries->whereHas('charity', function ($query) use ($request) {
                $query->where('ref', $request->charity);
            });
        }

        if ($request->filled('partner')) {
            $enquiries = $enquiries->whereHas('partnerChannel', function ($query) use ($request) {
                $query->whereHas('partner', function ($query) use ($request) {
                    $query->where('ref', $request->partner);
                });
            });
        }

        if ($request->filled('channel')) {
            $enquiries = $enquiries->whereHas('partnerChannel', function ($query) use ($request) {
                $query->where('ref', $request->channel);
            });
        }

        if ($request->filled('status')) {
            $enquiries = $enquiries->status(ExternalEnquiryStatusEnum::tryFrom($request->status));
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
        return ExternalEnquiryStatistics::generateStatsSummary($type, $year, $status, $category, $period);
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
        return ExternalEnquiryStatistics::generateYearGraphData($type, $year, $status, $category, $period);
    }
}
