<?php

namespace App\Services\DataServices;

use App\Http\Helpers\AccountType;
use App\Enums\InvoiceItemTypeEnum;
use App\Http\Helpers\FormatNumber;
use App\Jobs\ProcessDataServiceExport;
use App\Services\Reporting\Enums\InvoiceStatisticsTypeEnum;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\InvoiceStatistics;
use App\Services\Reporting\ParticipantStatistics;
use App\Services\Reporting\Traits\InvoiceStatsTrait;
use App\Services\TimePeriodReferenceService;
use App\Traits\Response;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use App\Services\ExportManager\FileExporterService;
use App\Http\Requests\InvoiceListingQueryParamsRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\ExportManager\Formatters\InvoiceExportableDataFormatter;

use App\Filters\PeriodFilter;
use App\Traits\DownloadTrait;
use App\Filters\DeletedFilter;
use App\Traits\PaginationTrait;
use App\Filters\InvoicesOrderByFilter;

use App\Enums\MonthEnum;
use App\Enums\InvoiceStatusEnum;

use App\Models\Invoice;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Traits\SiteTrait;

class InvoiceDataService extends DataService implements DataServiceInterface
{
    use DownloadTrait, InvoiceStatsTrait, Response,SiteTrait;

    /**
     * @param  mixed    $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder
    {
        if($request instanceof InvoiceListingQueryParamsRequest) {
            return $this->getFilteredInvoicesQuery($request);
        }else{
            return $this->getFilteredInvoicesQueryExport($request);
        }
    }

    /**
     * @param  mixed                       $request
     * @return LengthAwarePaginator|array
     */
    public function getPaginatedList(mixed $request): LengthAwarePaginator|array
    {
        if($request instanceof InvoiceListingQueryParamsRequest) {
            $data['invoices'] = $this->paginate($this->getFilteredInvoicesQuery($request)['invoices']);
 
            if (isset($this->getFilteredInvoicesQuery($request)['unpaidTotal'])) {
                $data['unpaidTotal'] = $this->getFilteredInvoicesQuery($request)['unpaidTotal'];
            }
 
        }
        if(is_array($request) && in_array('export', $request) ){
 
            $data['invoices'] = $this->paginate($this->getFilteredInvoicesQueryExport($request)['invoices']);
 
            if (isset($this->getFilteredInvoicesQueryExport($request)['unpaidTotal'])) {
                $data['unpaidTotal'] = $this->getFilteredInvoicesQueryExport($request)['unpaidTotal'];
            }
 
        }
 
        return $data;
    }

    /**
     * @param  string  $ref
     * @return Invoice
     */
    public function edit(string $ref): Invoice
    {
        $invoice = Invoice::with(['invoiceItems' => function ($query) {
            $query->appendsOnly([
                'formatted_price',
                'formatted_discount',
                'final_price',
                'formatted_final_price',
                'formatted_type',
                'note'
            ]);
        }, 'invoiceItems.invoiceItemable', 'upload']);

        $invoice = $invoice->whereHas('site', function($query) {
            $query->makingRequest();
        })->filterByAccess();

        if (AccountType::isAdmin()) { // Only the admin can access deleted invoices
            $invoice = $invoice->withTrashed();
        }

        $invoice = $invoice->where('ref', $ref)
            ->firstOrFail();

        foreach ($invoice->invoiceItems as $key => $item) {
            $invoice->invoiceItems[$key]['label'] = $item->loadRelationsThenGetLabel();
        }

        return $invoice;
    }

    /**
     * @param  mixed  $request
     * @return \Illuminate\Support\Collection
     */
    public function getExportList(mixed $request): \Illuminate\Support\Collection
    {
       
        if($request instanceof InvoiceListingQueryParamsRequest) {
            $data['invoices'] = $this->getFilteredInvoicesQuery($request)['invoices']->get();
 
            if (isset($this->getFilteredInvoicesQuery($request)['unpaidTotal'])) {
                $data['unpaidTotal'] = $this->getFilteredInvoicesQuery($request)['unpaidTotal'];
            }
        }
        if(in_array('export', $request->query()) ){
            $data['invoices'] = $this->getFilteredInvoicesQueryExport($request)['invoices']->get();
 
            if (isset($this->getFilteredInvoicesQueryExport($request)['unpaidTotal'])) {
                $data['unpaidTotal'] = $this->getFilteredInvoicesQueryExport($request)['unpaidTotal'];
            }
        }
        return collect($data);
    }

    /**
     * param mixed  $request
     * @return array
     */
    private function getFilteredInvoicesQueryExport(mixed $request): array
    {
        $invoices = Invoice::with(['invoiceable', 'site', 'invoiceItems.invoiceItemable', 'upload']);
 
        $invoices = $invoices->whereHas('site', function($query) use($request){
            $query->where('id', $request->site_id);
        })->filterByAccess()
          ->filterListBy(new PeriodFilter($request))
          ->filterListBy(new DeletedFilter($request))
          ->filterListBy(new InvoicesOrderByFilter($request));
    
        $invoices = $invoices->whereHas('site', function($query) use ($request) {
            $query->where('id', $request->site_id);
        })->when(
            $request->has('type'),
            fn ($query) => $query->whereHas('invoiceItems', function ($query) use ($request) {
                $query->where('type', InvoiceItemTypeEnum::from($request->type));
            })
        )->when(
            $request->has('status'),
            fn ($query) => $query->where('status', InvoiceStatusEnum::from($request->status))
        )->when(
            $request->has('held'),
            fn ($query) => $query->where('held', $request->held)
        )->when(
            $request->has('month'),
            fn ($query) => $query->whereMonth('issue_date', '=', MonthEnum::from($request->month)->value)
        )->when(
            $request->has('year'),
            fn ($query) => $query->whereYear('issue_date', '=', $request->year)
        )->when(
            $request->has('price'),
            fn ($query) => $query->where(function ($query) use ($request) {
                $query->whereBetween('price', $request->price)
                    ->orWhereHas('invoiceItems', function ($query) use ($request) {
                        $query->whereBetween('price', $request->price);
                    });
            })
        )->when(
            $request->has('term'),
            fn ($query) => $query->where(function($query) use ($request) {
                $query->where('name', 'like', '%'.$request->term.'%')
                    ->orWhere('description', 'like', '%'.$request->term.'%')
                    ->orWhere('price', 'like', '%'.$request->term.'%')
                    ->orWhere('po_number', 'like', '%'.$request->term.'%')
                    ->orWhere('issue_date', 'like', '%'.$request->term.'%')
                    ->orWhere('due_date', 'like', '%'.$request->term.'%');
            })
        )->when(
            !$request->has('order_by'), // Default Ordering
            fn($query) => $query->orderByDesc('created_at')
        );
    
        $data = [
            'invoices' => $invoices
        ];
    
        if ($request->has('status')) {
            if ($request['status'] == InvoiceStatusEnum::Unpaid->value && AccountType::isAccountManager()) {
                $_invoices = (clone $invoices)->sum('price'); // Clone invoices to avoid modifying the original query
                $data['unpaidTotal'] = $_invoices;
            }
        }
    
        return $data;
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
                new InvoiceExportableDataFormatter,
                'invoices'
            )),
            $request,
            $request->user(),
            $site,
        );

        return $this->success('The exported file will be sent to your email shortly.');
    }

    /**
     * @param  InvoiceListingQueryParamsRequest  $request
     * @return array
     */
    private function getFilteredInvoicesQuery(InvoiceListingQueryParamsRequest $request): array
    {
        $invoices = Invoice::with(['invoiceable', 'site', 'invoiceItems.invoiceItemable', 'upload']);

        $invoices = $invoices->whereHas('site', function($query) {
            $query->makingRequest();
        })->filterByAccess()
        ->filterListBy(new PeriodFilter)
        ->filterListBy(new DeletedFilter)
        ->filterListBy(new InvoicesOrderByFilter);

        $invoices = $invoices->whereHas('site', function($query) {
            $query->makingRequest();
        })->when(
            $request->filled('type'),
            fn ($query) => $query->whereHas('invoiceItems', function ($query) use ($request) {
                $query->where('type', InvoiceItemTypeEnum::from($request->type));
            })
        )->when(
            $request->filled('status'),
            fn ($query) => $query->where('status', InvoiceStatusEnum::from($request->status))
        )->when(
            $request->filled('held'),
            fn ($query) => $query->where('held', $request->held)
        )->when(
            $request->filled('month'),
            fn ($query) => $query->whereMonth('issue_date', '=', MonthEnum::from($request->month)->value)
        )->when(
            $request->filled('year'),
            fn ($query) => $query->whereYear('issue_date', '=', $request->year)
        )->when(
            $request->filled('price'),
            fn ($query) => $query->where(function ($query) use ($request) {
                $query->whereBetween('price', $request->price)
                    ->orWhereHas('invoiceItems', function ($query) use ($request) {
                        $query->whereBetween('price', $request->price);
                    });
                })
        )->when(
            $request->filled('term'),
            fn ($query) => $query->where(function($query) use ($request) {
                $query->where('name', 'like', '%'.$request->term.'%')
                    ->orWhere('description', 'like', '%'.$request->term.'%')
                    ->orWhere('price', 'like', '%'.$request->term.'%')
                    ->orWhere('po_number', 'like', '%'.$request->term.'%')
                    ->orWhere('issue_date', 'like', '%'.$request->term.'%')
                    ->orWhere('due_date', 'like', '%'.$request->term.'%');
                    // ->orWhere(function($query) use ($request) {
                    //     $query->whereHasMorph(
                    //         'invoiceable',
                    //         [Participant::class],
                    //         function($query) use ($request) {
                    //             $query->whereHas('charity', function ($query) use ($request) {
                    //                 $query->where('name', 'like', '%'.$request->term.'%');
                    //             });
                    //         }
                    //     );
                    // });
            })
        )->when(! $request->filled('order_by'), // Default Ordering
            fn($query) => $query->orderByDesc('created_at')
        );

        $data = [
            'invoices' => $invoices
        ];

        if ($request->filled('status')) {
			if ($request->status == InvoiceStatusEnum::Unpaid->value && AccountType::isAccountManager()) {
                $_invoices = $invoices->clone();
                $_invoices = $_invoices->sum('price');
                $data['unpaidTotal'] = $_invoices;
			}
        }

        return $data;
    }

    /**
     * @param $type
     * @param $year
     * @param $status
     * @param $period
     * @return array
     */
    public function generateStatsSummary($type, $year, $status, $period): array
    {
        return InvoiceStatistics::generateStatsSummary($type, $year, $status, $period);
    }

    /**
     * @param $type
     * @param $year
     * @param $status
     * @param $period
     * @return array
     */
    public function generateYearGraphData($type, $year, $status, $period): array
    {
        return InvoiceStatistics::generateYearGraphData($type, $year, $status, $period);
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param string|null $status
     * @param string|null $category
     * @param int|null $userId
     * @param TimePeriodReferenceService|null $period
     * @return \array[][]
     */
    public static function invoicesStatsData(?StatisticsEntityEnum $entity, ?int $year, ?string $status, ?string $category = null, ?int $userId = null, ?TimePeriodReferenceService $period = null): array
    {
        return [
            'name' => InvoiceStatisticsTypeEnum::tryFrom($category)?->formattedName() ?? InvoiceStatisticsTypeEnum::Invoices->formattedName(),
            'total' => FormatNumber::formatWithCurrency(self::invoicesSummaryQuery($entity, $year, $status, $category, $userId, $period)->sum('price')),
            'percent_change' => self::invoicesSummaryPercentChange($entity, $year, $status, $category, $userId, $period),
            'type_param_value' => InvoiceStatisticsTypeEnum::tryFrom($category)?->value ?? InvoiceStatisticsTypeEnum::Invoices->value
        ];
    }


    /**
     * @param StatisticsEntityEnum|null $entity
     * @param string|null $status
     * @param string|null $category
     * @param int|null $year
     * @param int|null $month
     * @param int|null $userId
     * @param TimePeriodReferenceService|null $period
     * @return Collection|\Illuminate\Support\Collection|array
     */
    public static function invoicesStackedChartData(?StatisticsEntityEnum $entity = null, ?string $status = null, ?string $category = null, ?int $year = null, ?int $month = null, ?int $userId = null, ?TimePeriodReferenceService $period = null): Collection|\Illuminate\Support\Collection|array
    {
        return Invoice::query()
            ->where('site_id', '=', clientSiteId())
            ->select(['status'])
            ->when($status, fn($query) => $query->where('status', '=', $status))
            ->distinct()
            ->get()
            ->map(function ($invoice) use ($entity, $category, $year, $month, $userId, $period) {
                $item = [];
                $item['name'] = $invoice->status->name;
                $item['total'] = self::invoicesSummaryQuery($entity, $year, $invoice->status->value, InvoiceItemTypeEnum::tryFrom($category)?->value, $userId, $period, $month)->sum('price');
                return $item;
            });
    }
}
