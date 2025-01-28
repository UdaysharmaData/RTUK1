<?php

namespace App\Services\Reporting\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Helpers\AccountType;
use App\Http\Helpers\FormatNumber;
use App\Models\Invoice;
use App\Modules\Event\Models\Event;
use App\Modules\Finance\Models\InternalTransaction;
use App\Modules\Participant\Models\Participant;
use App\Services\PercentageChange;
use App\Services\Reporting\DashboardStatistics;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Traits\Response;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use DatePeriod;
use DateTime;
use DateInterval;

class DashboardStatisticsController extends Controller
{
    use Response;

    /**
     * Dashboard Stats
     *
     * Get Dashboard Stats Summary.
     *
     * @group Dashboard
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam year string Specifying method of filtering query by year. Example: 2022
     * @queryParam status string Specifying method of filtering query by status.
     * @queryParam category string Specifying method of filtering query by category (ref for event categories). Example: 98677146-d86a-4b10-a694-d79eb66e8220
     * @queryParam type string Specifying method of filtering query by type. Example: invoices
     * @queryParam period string Specifying method of filtering query by time period. Example: 24h
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        $validator = DashboardStatistics::getParamsValidator(false);

        if ($validator->fails()) {
            return $this->error(
                'Invalid stats parameter(s) specified.',
                422,
                $validator->errors()->messages()
            );
        }

        $type = request('type');
        $year = request('year');
        $status = request('status');
        $period = request('period');
        $category = request('category');
        $parameters = array_filter(request()->query());

        try {
            list($status, $year, $category, $period) = DashboardStatistics::setParams($type, $status, $category, $year, $period);

            $stats = DashboardStatistics::generateStatsSummary($type, $year, $status, $category, $period);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while fetching stats.', 400, $exception->getMessage());
        }

        return $this->success('Dashboard Stats Summary', 200, array_merge($stats, ['query_params' => $parameters]));
    }

    /**
     * Dashboard Latest Participants
     *
     * Get Latest 4 participants
     *
     * @group Dashboard
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam limit string Specifying method of filtering query by number of items to return. Example: 4
     *
     * @return JsonResponse
     */
    public function latestParticipants(): JsonResponse
    {
        $limit = request('limit', 4);
        $parameters = array_filter(request()->query());

        try {
            $data = DashboardStatistics::latestParticipants($limit);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching stats.', 400, $exception->getMessage());
        }

        return $this->success('Dashboard latest participants.', 200, [
            'latest_participants' => $data,
            'query_params' => $parameters
        ]);
    }

    /**
     * Dashboard Chart data
     *
     * @group Dashboard
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam type string required Specifying method of filtering query by type. Example: invoices
     * @queryParam year string Specifying method of filtering query by year. Example: 2022
     * @queryParam status string Specifying method of filtering query by status.
     * @queryParam category string Specifying method of filtering query by category (ref for event categories). Example: 98677146-d86a-4b10-a694-d79eb66e8220
     * @queryParam period string Specifying method of filtering query by time period. Example: 24h
     *
     * @return JsonResponse
     */
    public function chart(): JsonResponse
    {
        $validator = DashboardStatistics::getParamsValidator();

        if ($validator->fails()) {
            return $this->error(
                'Invalid chart parameter(s) specified.',
                422,
                $validator->errors()->messages()
            );
        }

        $type = request('type');
        $year = request('year');
        $status = request('status');
        $period = request('period');
        $category = request('category');
        $parameters = array_filter(request()->query());

        try {
            list($status, $year, $category) = DashboardStatistics::setParams($type, $status, $category, $year, $period);

            $stats = DashboardStatistics::generateYearGraphData($type, $year, $status, $category, $period);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error("An error occurred while fetching $type chart data.", 400, $exception->getMessage());
        }

        return $this->success("Dashboard $type chart data.", 200, [
            'stats' => $stats,
            'query_params' => $parameters
        ]);
    }

    public function netRevenue(): JsonResponse
    {
        $type = request('type', 'this_month'); // Set default to 'Today' if type is not provided

        if (!in_array($type, ['today', 'yesterday', 'this_week', 'this_month', 'this_quarter', 'this_year', 'custom'])) {
            return response()->json([
                'code' => 400,
                'message' => 'Invalid type. Supported types are: Today, Yesterday, This Week, This Month.',
                'net_revenue' => 0,
                'daily_revenue' => []
            ], 400);
        }

        switch ($type) {
            case 'today':
                $from_date = date('Y-m-d 00:00:00', strtotime('today'));
                $to_date = date('Y-m-d 23:59:59', strtotime('today'));
                break;
            case 'yesterday':
                $from_date = date('Y-m-d 00:00:00', strtotime('yesterday'));
                $to_date = date('Y-m-d 23:59:59', strtotime('yesterday'));
                break;
            case 'this_week':
                $from_date = date('Y-m-d 00:00:00', strtotime('monday this week'));
                $to_date = date('Y-m-d 23:59:59', strtotime('sunday this week'));
                break;
            case 'this_month':
                $from_date = date('Y-m-01 00:00:00');
                $to_date = date('Y-m-t 23:59:59');
                break;
            case 'custom':
                $from_date = request('from_date');
                $to_date = request('to_date');
                $to_date = date('Y-m-d', strtotime($to_date . ' +1 day'));
                break;
            case 'this_quarter':
                $currentMonth = date('n');
                $currentYear = date('Y');

                // Determine the start and end dates for the current quarter
                $quarter_start = new DateTime(date('Y-m-d', mktime(0, 0, 0, ceil($currentMonth / 3) * 3 - 2, 1, $currentYear)));
                $quarter_end = new DateTime(date('Y-m-d', mktime(0, 0, 0, ceil($currentMonth / 3) * 3 + 1, 0, $currentYear)));

                $from_date = $quarter_start->format('Y-m-d 00:00:00');
                $to_date = $quarter_end->format('Y-m-d 23:59:59');
                break;
            case 'this_year':
                $currentYear = date('Y');
                // Determine the start and end dates for the current year
                $year_start = new DateTime("$currentYear-01-01 00:00:00");
                $year_end = new DateTime("$currentYear-12-31 23:59:59");

                $from_date = $year_start->format('Y-m-d H:i:s');
                $to_date = $year_end->format('Y-m-d H:i:s');
                break;
        }

        // Calculate net revenue for the main date range
        $gross_sale = Invoice::query()
            ->where('site_id', '=', clientSiteId())
            ->whereBetween('created_at', [$from_date, $to_date])
            ->whereNull('deleted_at')
            ->sum('price');

        $refund_amount = InternalTransaction::query()
            ->join('transactions', 'transactions.id', '=', 'internal_transactions.transaction_id')
            ->join('accounts', 'accounts.id', '=', 'internal_transactions.account_id')
            ->where('transactions.site_id', '=', clientSiteId())
            ->where('internal_transactions.type', '=', 'credit')
            ->whereBetween('internal_transactions.created_at', [$from_date, $to_date])
            ->whereNull('internal_transactions.deleted_at')
            ->sum('internal_transactions.amount');

        $net_revenue = $gross_sale - $refund_amount;

        // Prepare daily net revenue data if the type is "This Week" or "This Month"
        $dailyGraph = [];
        if (in_array($type, ['this_week', 'this_month', 'custom'])) {
            $start_date = new DateTime($from_date);
            $end_date = new DateTime($to_date);

            $interval = new DateInterval('P1D');
            $period = new DatePeriod($start_date, $interval, $end_date);

            foreach ($period as $date) {
                $day_start = $date->format('Y-m-d 00:00:00');
                $day_end = $date->format('Y-m-d 23:59:59');

                $daily_gross_sale = Invoice::query()
                    ->where('site_id', '=', clientSiteId())
                    ->whereBetween('created_at', [$day_start, $day_end])
                    ->whereNull('deleted_at')
                    ->sum('price');

                $daily_refund_amount = InternalTransaction::query()
                    ->join('transactions', 'transactions.id', '=', 'internal_transactions.transaction_id')
                    ->join('accounts', 'accounts.id', '=', 'internal_transactions.account_id')
                    ->where('transactions.site_id', '=', clientSiteId())
                    ->where('internal_transactions.type', '=', 'credit')
                    ->whereBetween('internal_transactions.created_at', [$day_start, $day_end])
                    ->whereNull('internal_transactions.deleted_at')
                    ->sum('internal_transactions.amount');

                $daily_net_revenue = $daily_gross_sale - $daily_refund_amount;

                $dailyGraph[] = [
                    'date' => $date->format('Y-m-d'),
                    'net_revenue' => FormatNumber::formatWithCurrency($daily_net_revenue)
                ];
            }
        }

        if (in_array($type, ['this_quarter', 'this_year'])) {
            $start_date = new DateTime($from_date);
            $end_date = new DateTime($to_date);

            $interval = new DateInterval('P1M');
            $period = new DatePeriod($start_date, $interval, $end_date);

            foreach ($period as $date) {
                $month_start = $date->format('Y-m-01 00:00:00');
                $month_end = $date->format('Y-m-t 23:59:59');

                $monthly_gross_sale = Invoice::query()
                    ->where('site_id', '=', clientSiteId())
                    ->whereBetween('created_at', [$month_start, $month_end])
                    ->whereNull('deleted_at')
                    ->sum('price');

                $monthly_refund_amount = InternalTransaction::query()
                    ->join('transactions', 'transactions.id', '=', 'internal_transactions.transaction_id')
                    ->join('accounts', 'accounts.id', '=', 'internal_transactions.account_id')
                    ->where('transactions.site_id', '=', clientSiteId())
                    ->where('internal_transactions.type', '=', 'credit')
                    ->whereBetween('internal_transactions.created_at', [$month_start, $month_end])
                    ->whereNull('internal_transactions.deleted_at')
                    ->sum('internal_transactions.amount');

                $monthly_net_revenue = $monthly_gross_sale - $monthly_refund_amount;

                $dailyGraph[] = [
                    'month' => $date->format('F Y'),
                    'net_revenue' => FormatNumber::formatWithCurrency($monthly_net_revenue)
                ];
            }
        }



        // Prepare hourly net revenue data for "Today" or "Yesterday"
        $hourlyGraph = [];
        if (in_array($type, ['today', 'yesterday'])) {
            $hourlyData = Invoice::query()
                ->selectRaw('HOUR(created_at) as hour, SUM(price) as gross_sale')
                ->where('site_id', '=', clientSiteId())
                ->whereBetween('created_at', [$from_date, $to_date])
                ->whereNull('deleted_at')
                ->groupByRaw('HOUR(created_at)')
                ->orderByRaw('HOUR(created_at)')
                ->get()
                ->mapWithKeys(function ($item) use ($from_date) {
                    $hour_start = date('Y-m-d H:00:00', strtotime($from_date) + $item->hour * 3600);
                    $hour_end = date('Y-m-d H:59:59', strtotime($from_date) + $item->hour * 3600);
                    $refunds = InternalTransaction::query()
                        ->join('transactions', 'transactions.id', '=', 'internal_transactions.transaction_id')
                        ->join('accounts', 'accounts.id', '=', 'internal_transactions.account_id')
                        ->where('transactions.site_id', '=', clientSiteId())
                        ->where('internal_transactions.type', '=', 'credit')
                        ->whereBetween('internal_transactions.created_at', [$hour_start, $hour_end])
                        ->whereNull('internal_transactions.deleted_at')
                        ->sum('internal_transactions.amount');
                    return [$item->hour => $item->gross_sale - $refunds];
                });

            for ($hour = 0; $hour < 24; $hour++) {
                $formatted_hour = date('h A', strtotime("$hour:00"));
                $hourlyGraph[] = [
                    'date' => date('Y-m-d', strtotime($from_date)),
                    'hour' => $formatted_hour,
                    'net_revenue' => FormatNumber::formatWithCurrency($hourlyData->get($hour, 0))
                ];
            }
        }

        return response()->json([
            'code' => 200,
            'message' => 'Net revenue data retrieved successfully.',
            'net_revenue' => FormatNumber::formatWithCurrency($net_revenue),
            'hourly_revenue' => $hourlyGraph,
            'daily_revenue' => $dailyGraph,
        ], 200);
    }

    public function eventDataSummary(): JsonResponse
    {
        $type = request('type', 'this_month'); // Set default to 'this_month' if type is not provided

        if (!in_array($type, ['today', 'yesterday', 'this_week', 'this_month', 'this_quarter', 'this_year', 'custom'])) {
            return response()->json([
                'code' => 400,
                'message' => 'Invalid type. Supported types are: today, yesterday, this_week, this_month, this_quarter, this_year, custom.'
            ], 400);
        }

        switch ($type) {
            case 'today':
                $from_date = date('Y-m-d 00:00:00', strtotime('today'));
                $to_date = date('Y-m-d 23:59:59', strtotime('today'));
                break;
            case 'yesterday':
                $from_date = date('Y-m-d 00:00:00', strtotime('yesterday'));
                $to_date = date('Y-m-d 23:59:59', strtotime('yesterday'));
                break;
            case 'this_week':
                $from_date = date('Y-m-d 00:00:00', strtotime('monday this week'));
                $to_date = date('Y-m-d 23:59:59', strtotime('sunday this week'));
                break;
            case 'this_month':
                $from_date = date('Y-m-01 00:00:00');
                $to_date = date('Y-m-t 23:59:59');
                break;
            case 'custom':
                $from_date = request('from_date');
                $to_date = request('to_date');
                $to_date = date('Y-m-d', strtotime($to_date . ' +1 day'));
                break;
            case 'this_quarter':
                $currentMonth = date('n');
                $currentYear = date('Y');

                // Determine the start and end dates for the current quarter
                $quarter_start = new DateTime(date('Y-m-d', mktime(0, 0, 0, ceil($currentMonth / 3) * 3 - 2, 1, $currentYear)));
                $quarter_end = new DateTime(date('Y-m-d', mktime(0, 0, 0, ceil($currentMonth / 3) * 3 + 1, 0, $currentYear)));

                $from_date = $quarter_start->format('Y-m-d 00:00:00');
                $to_date = $quarter_end->format('Y-m-d 23:59:59');
                break;
            case 'this_year':
                $currentYear = date('Y');
                // Determine the start and end dates for the current year
                $year_start = new DateTime("$currentYear-01-01 00:00:00");
                $year_end = new DateTime("$currentYear-12-31 23:59:59");

                $from_date = $year_start->format('Y-m-d H:i:s');
                $to_date = $year_end->format('Y-m-d H:i:s');
                break;
        }
        $entity = (object) [
            'name' => 'Dashboard',
            'value' => 'dashboard'
        ];

        $baseQuery = Event::query()
            ->select('id','name','created_at')
            ->when($entity == StatisticsEntityEnum::Enquiry, fn($query) => $query->whereHas('enquiries', function ($query) {
                $query->where('site_id', clientSiteId());
            }))
            ->when($entity == StatisticsEntityEnum::ExternalEnquiry, fn($query) => $query->whereHas('externalEnquiries', function ($query) {
                $query->where('site_id', clientSiteId());
            }))
            ->whereHas('eventCategories', function ($query) {
                $query->where('site_id', clientSiteId());
            })
            ->whereBetween('created_at', [$from_date, $to_date])
            ->whereNull('deleted_at');

        $perPage = request('per_page', 10);
        $paginatedData = $baseQuery->paginate($perPage);

        $filteredEvents = $paginatedData->map(function ($event) {

            $net_sold = DB::table('participants')
                ->join('event_event_category', 'participants.event_event_category_id', '=', 'event_event_category.id')
                ->join('event_categories', 'event_categories.id', '=', 'event_event_category.event_category_id')
                ->where('event_categories.site_id', clientSiteId())
                ->where('event_event_category.event_id', $event['id'])
                ->count();

            $net_revenue_event = DB::table('participants')
                ->join('event_event_category', 'participants.event_event_category_id', '=', 'event_event_category.id')
                ->join('event_categories', 'event_categories.id', '=', 'event_event_category.event_category_id')
                ->where('event_categories.site_id', clientSiteId())
                ->where('event_event_category.event_id', $event['id'])
                ->sum('event_event_category.local_fee');

            return [
                'id' => $event['id'],
                'name' => $event['name'],
                'net_sold' => $net_sold,
                'net_revenue_event' => $net_revenue_event,
                'created_at' => date('Y-m-d', strtotime($event['created_at'])),
                'status' => $event['status'],
                'slug' => $event['slug'],
                'partner_event' => $event['partner_event'],
                'local_registration_fee_range' => $event['local_registration_fee_range'],
                'international_registration_fee_range' => $event['international_registration_fee_range'],
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => 'Event data retrieved successfully.',
            'event_widget' => $paginatedData->total(),
            'event_data' => $filteredEvents,
            'pagination' => [
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
                'per_page' => $paginatedData->perPage(),
                'total' => $paginatedData->total()
            ]
        ], 200);
    }

    public function netRevenueEventSummary(): array
    {
        try {
            $type = request('type', 'this_month'); // Set default to 'this_month' if type is not provided

            if (!in_array($type, ['today', 'yesterday', 'this_week', 'this_month', 'this_quarter', 'this_year', 'custom'])) {
                return response()->json([
                    'code' => 400,
                    'message' => 'Invalid type. Supported types are: today, yesterday, this_week, this_month, this_quarter, this_year, custom.'
                ], 400);
            }

            switch ($type) {
                case 'today':
                    $from_date = date('Y-m-d 00:00:00', strtotime('today'));
                    $to_date = date('Y-m-d 23:59:59', strtotime('today'));
                    break;
                case 'yesterday':
                    $from_date = date('Y-m-d 00:00:00', strtotime('yesterday'));
                    $to_date = date('Y-m-d 23:59:59', strtotime('yesterday'));
                    break;
                case 'this_week':
                    $from_date = date('Y-m-d 00:00:00', strtotime('monday this week'));
                    $to_date = date('Y-m-d 23:59:59', strtotime('sunday this week'));
                    break;
                case 'this_month':
                    $from_date = date('Y-m-01 00:00:00');
                    $to_date = date('Y-m-t 23:59:59');
                    break;
                case 'custom':
                    $from_date = request('from_date');
                    $to_date = request('to_date');
                    $to_date = date('Y-m-d', strtotime($to_date . ' +1 day'));
                    break;
                case 'this_quarter':
                    $currentMonth = date('n');
                    $currentYear = date('Y');

                    // Determine the start and end dates for the current quarter
                    $quarter_start = new DateTime(date('Y-m-d', mktime(0, 0, 0, ceil($currentMonth / 3) * 3 - 2, 1, $currentYear)));
                    $quarter_end = new DateTime(date('Y-m-d', mktime(0, 0, 0, ceil($currentMonth / 3) * 3 + 1, 0, $currentYear)));

                    $from_date = $quarter_start->format('Y-m-d 00:00:00');
                    $to_date = $quarter_end->format('Y-m-d 23:59:59');
                    break;
                case 'this_year':
                    $currentYear = date('Y');
                    // Determine the start and end dates for the current year
                    $year_start = new DateTime("$currentYear-01-01 00:00:00");
                    $year_end = new DateTime("$currentYear-12-31 23:59:59");

                    $from_date = $year_start->format('Y-m-d H:i:s');
                    $to_date = $year_end->format('Y-m-d H:i:s');
                    break;
            }

            $gross_sale = Invoice::with(['invoiceItems.invoiceItemable'])
                ->where('site_id', '=', clientSiteId())
                ->whereBetween('created_at', [$from_date, $to_date])
                ->where('price', '>=', 0)
                ->sum('price');

            $refund_amount = InternalTransaction::query()
                ->join('transactions', 'transactions.id', '=', 'internal_transactions.transaction_id')
                ->join('accounts', 'accounts.id', '=', 'internal_transactions.account_id')
                ->where('transactions.site_id', '=', clientSiteId())
                ->where('internal_transactions.type', '=', 'credit')
                ->whereBetween('internal_transactions.created_at', [$from_date, $to_date])
                ->whereNull('internal_transactions.deleted_at')
                ->sum('internal_transactions.amount');

            $net_revenue = $gross_sale - $refund_amount;

            $dailyGraph = [];
            if (in_array($type, ['this_week', 'this_month', 'custom'])) {
                $start_date = new DateTime($from_date);
                $end_date = new DateTime($to_date);

                $interval = new DateInterval('P1D');
                $period = new DatePeriod($start_date, $interval, $end_date);

                foreach ($period as $date) {
                    $day_start = $date->format('Y-m-d 00:00:00');
                    $day_end = $date->format('Y-m-d 23:59:59');

                    $daily_gross_sale = Invoice::with(['invoiceItems.invoiceItemable'])
                        ->where('site_id', '=', clientSiteId())
                        ->whereBetween('created_at', [$day_start, $day_end])
                        ->sum('price');

                    $daily_refund_amount = InternalTransaction::query()
                        ->join('transactions', 'transactions.id', '=', 'internal_transactions.transaction_id')
                        ->join('accounts', 'accounts.id', '=', 'internal_transactions.account_id')
                        ->where('transactions.site_id', '=', clientSiteId())
                        ->where('internal_transactions.type', '=', 'credit')
                        ->whereBetween('internal_transactions.created_at', [$day_start, $day_end])
                        ->whereNull('internal_transactions.deleted_at')
                        ->sum('internal_transactions.amount');

                    $daily_net_revenue = $daily_gross_sale - $daily_refund_amount;
                    $dailyGraph[] = [
                        'date' => $date->format('Y-m-d'),
                        'net_revenue' => FormatNumber::formatWithCurrency($daily_net_revenue)
                    ];
                }
            }

            if (in_array($type, ['this_quarter', 'this_year'])) {
                $start_date = new DateTime($from_date);
                $end_date = new DateTime($to_date);

                $interval = new DateInterval('P1M');
                $period = new DatePeriod($start_date, $interval, $end_date);

                foreach ($period as $date) {
                    $month_start = $date->format('Y-m-01 00:00:00');
                    $month_end = $date->format('Y-m-t 23:59:59');

                    $monthly_gross_sale = Invoice::with(['invoiceItems.invoiceItemable'])
                        ->where('site_id', '=', clientSiteId())
                        ->whereBetween('created_at', [$month_start, $month_end])
                        ->sum('price');

                    $monthly_refund_amount = InternalTransaction::query()
                        ->join('transactions', 'transactions.id', '=', 'internal_transactions.transaction_id')
                        ->join('accounts', 'accounts.id', '=', 'internal_transactions.account_id')
                        ->where('transactions.site_id', '=', clientSiteId())
                        ->where('internal_transactions.type', '=', 'credit')
                        ->whereBetween('internal_transactions.created_at', [$month_start, $month_end])
                        ->whereNull('internal_transactions.deleted_at')
                        ->sum('internal_transactions.amount');

                    $monthly_net_revenue = $monthly_gross_sale - $monthly_refund_amount;

                    $dailyGraph[] = [
                        'month' => $date->format('F Y'),
                        'net_revenue' => FormatNumber::formatWithCurrency($monthly_net_revenue)
                    ];
                }
            }

            $hourlyGraph = [];
            if (in_array($type, ['today', 'yesterday'])) {
                $hourlyData = Invoice::with(['invoiceItems.invoiceItemable'])
                        ->selectRaw('HOUR(created_at) as hour, SUM(price) as gross_sale')
                        ->where('site_id', '=', clientSiteId())
                        ->whereBetween('created_at', [$from_date, $to_date])
                        ->whereNull('deleted_at')
                        ->groupByRaw('HOUR(created_at)')
                        ->orderByRaw('HOUR(created_at)')
                        ->get()
                    ->mapWithKeys(function ($item) use ($from_date) {
                        $hour_start = date('Y-m-d H:00:00', strtotime($from_date) + $item->hour * 3600);
                        $hour_end = date('Y-m-d H:59:59', strtotime($from_date) + $item->hour * 3600);
                        $refunds = InternalTransaction::query()
                            ->join('transactions', 'transactions.id', '=', 'internal_transactions.transaction_id')
                            ->join('accounts', 'accounts.id', '=', 'internal_transactions.account_id')
                            ->where('transactions.site_id', '=', clientSiteId())
                            ->where('internal_transactions.type', '=', 'credit')
                            ->whereBetween('internal_transactions.created_at', [$hour_start, $hour_end])
                            ->whereNull('internal_transactions.deleted_at')
                            ->sum('internal_transactions.amount');
                        return [$item->hour => $item->gross_sale - $refunds];
                    });

                for ($hour = 0; $hour < 24; $hour++) {
                    $formatted_hour = date('h A', strtotime("$hour:00"));
                    $hourlyGraph[] = [
                        'date' => date('Y-m-d', strtotime($from_date)),
                        'hour' => $formatted_hour,
                        'net_revenue' => FormatNumber::formatWithCurrency($hourlyData->get($hour, 0))
                    ];
                }
            }

            $invoices = Invoice::with(['invoiceItems.invoiceItemable'])
                ->where('site_id', '=', clientSiteId())
                ->whereBetween('created_at', [$from_date, $to_date])
                ->get();

            $eventCount = $invoices->flatMap(function ($invoice) {
                return $invoice->invoiceItems->map(function ($item) {
                    return $item->invoiceItemable->eventEventCategory->event_id ?? null;
                });
            })->filter()->count();

            $eventIds = $invoices->flatMap(function ($invoice) {
                return $invoice->invoiceItems->map(function ($item) {
                    return [
                        'event_id' => $item->invoiceItemable->eventEventCategory->event_id ?? null,
                        'price' => $item->invoiceItemable->eventEventCategory->local_fee ?? null,
                        'created_at' => $item->invoiceItemable->eventEventCategory->created_at ?? null
                    ];
                });
            })->filter(function ($item) {
                return !is_null($item['event_id']);
            })->values()->toArray();

            $eventQuantities = [];
            $eventTotalPrices = [];
            $eventCreatedAt = [];

            foreach ($eventIds as $item) {
                $eventId = $item['event_id'];
                $quantity = isset($eventQuantities[$eventId]) ? $eventQuantities[$eventId] + 1 : 1;
                $eventQuantities[$eventId] = $quantity;

                $totalPrice = isset($eventTotalPrices[$eventId]) ? $eventTotalPrices[$eventId] + $item['price'] : $item['price'];
                $eventTotalPrices[$eventId] = $totalPrice;

                if (!isset($eventCreatedAt[$eventId])) {
                    $eventCreatedAt[$eventId] = $item['created_at'];
                }
            }

            $events = Event::whereIn('id', array_keys($eventQuantities))->pluck('name', 'id');

            $result = [];
            foreach ($eventQuantities as $eventId => $quantity) {
                if (isset($events[$eventId])) {
                    $result[] = [
                        'event_id' => $eventId,
                        'event_name' => $events[$eventId],
                        'quantity' => $quantity,
                        'total_price' => $eventTotalPrices[$eventId],
                        'created_at' => date('Y-m-d', strtotime($eventCreatedAt[$eventId]))
                    ];
                }
            }

            usort($result, function ($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            $searchTerm = request()->input('term', '');
            $orderBy = request()->input('order_by', '');

            $sortFields = [];
            $sortOrders = [];

            if ($orderBy) {
                $orderByParts = explode(',', $orderBy);
                foreach ($orderByParts as $part) {
                    list($field, $order) = explode(':', $part);
                    $sortFields[] = $field;
                    $sortOrders[] = $order;
                }
            }

            $filteredResult = array_filter($result, function($item) use ($searchTerm) {
                return stripos($item['event_name'], $searchTerm) !== false;
            });

            usort($filteredResult, function($a, $b) use ($sortFields, $sortOrders) {
                foreach ($sortFields as $index => $sortField) {
                    $sortOrder = $sortOrders[$index] ?? 'asc'; // Default to 'asc' if no order specified
                    $valueA = $a[$sortField] ?? '';
                    $valueB = $b[$sortField] ?? '';

                    if ($sortField === 'total_price') {
                        // Convert values to floats for numeric comparison
                        $valueA = floatval($valueA);
                        $valueB = floatval($valueB);
                    }

                    if ($valueA != $valueB) {
                        return ($sortOrder === 'asc') ? $valueA <=> $valueB : $valueB <=> $valueA;
                    }
                }
                return 0; // If all sort fields are equal, maintain the original order
            });

            // Pagination
            $page = request()->input('page', 1);
            $perPage = 5; // Number of items per page
            $offset = ($page - 1) * $perPage;
            $paginatedResult = array_slice($filteredResult, $offset, $perPage);
            $totalItems = count($filteredResult);
            $totalPages = ceil($totalItems / $perPage);

            return [
                'message' => 'Net Revenue AND Event data retrieved successfully.',
                'status' => 200,
                'net_revenue_widgets' => FormatNumber::formatWithCurrency($net_revenue),
                'event_widgets' => $eventCount,
                'event_data' => $paginatedResult,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'items_per_page' => $perPage,
                'hourly_revenue' => $hourlyGraph,
                'daily_revenue' => $dailyGraph
            ];
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'An error occurred while retrieving data: ' . $e->getMessage()
            ], 500);
        }
    }

    public static function entriesSummary(): JsonResponse
    {
        try {
            // Define type and validate
            $type = request('type', 'this_month'); // Default to 'this_year' if type is not provided

            if (!in_array($type, ['today', 'yesterday', 'this_week', 'this_month', 'this_quarter', 'this_year', 'custom'])) {
                throw new \InvalidArgumentException('Invalid type. Supported types are: today, yesterday, this_week, this_month, this_quarter, this_year, custom.', 400);
            }

            // Set default dates
            $from_date = null;
            $to_date = null;

            // Set date range based on type
            switch ($type) {
                case 'today':
                    $from_date = date('Y-m-d 00:00:00', strtotime('today'));
                    $to_date = date('Y-m-d 23:59:59', strtotime('today'));
                    break;
                case 'yesterday':
                    $from_date = date('Y-m-d 00:00:00', strtotime('yesterday'));
                    $to_date = date('Y-m-d 23:59:59', strtotime('yesterday'));
                    break;
                case 'this_week':
                    $from_date = date('Y-m-d 00:00:00', strtotime('monday this week'));
                    $to_date = date('Y-m-d 23:59:59', strtotime('sunday this week'));
                    break;
                case 'this_month':
                    $from_date = date('Y-m-01 00:00:00');
                    $to_date = date('Y-m-t 23:59:59');
                    break;
                case 'custom':
                    $from_date = request('from_date');
                    $to_date = request('to_date');
                    if (!$from_date || !$to_date) {
                        throw new \InvalidArgumentException('Custom date range requires both from_date and to_date parameters.', 400);
                    }
                    $to_date = date('Y-m-d', strtotime($to_date . ' +1 day'));
                    break;
                case 'this_quarter':
                    $currentMonth = date('n');
                    $currentYear = date('Y');

                    // Determine the start and end dates for the current quarter
                    $quarter_start = new DateTime(date('Y-m-d', mktime(0, 0, 0, ceil($currentMonth / 3) * 3 - 2, 1, $currentYear)));
                    $quarter_end = new DateTime(date('Y-m-d', mktime(0, 0, 0, ceil($currentMonth / 3) * 3 + 1, 0, $currentYear)));

                    $from_date = $quarter_start->format('Y-m-d 00:00:00');
                    $to_date = $quarter_end->format('Y-m-d 23:59:59');
                    break;
                case 'this_year':
                    $currentYear = date('Y');
                    // Determine the start and end dates for the current year
                    $year_start = new DateTime("$currentYear-01-01 00:00:00");
                    $year_end = new DateTime("$currentYear-12-31 23:59:59");

                    $from_date = $year_start->format('Y-m-d H:i:s');
                    $to_date = $year_end->format('Y-m-d H:i:s');
                    break;
            }

            $entries = Participant::select(['id', 'ref', 'event_event_category_id', 'charity_id', 'corporate_id', 'status', 'created_at', 'updated_at'])
                ->with([
                    'eventEventCategory.eventCategory:id,ref,name,slug',
                    'eventEventCategory.event:id,ref,name,slug',
                    'invoiceItem.invoice.upload',
                ])
                ->whereHas('eventEventCategory', function ($query) {
                    $query->whereHas('eventCategory', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        });
                    });
                })
                ->when($from_date && $to_date, function ($query) use ($from_date, $to_date) {
                    $query->whereBetween('created_at', [$from_date, $to_date]);
                })
                ->orderByDesc('created_at')
                ->count(); // Pagination with 5 entries per page


            $dailyGraph = [];
            if (in_array($type, ['this_week', 'this_month', 'custom'])) {
                $start_date = new DateTime($from_date);
                $end_date = new DateTime($to_date);

                $interval = new DateInterval('P1D');
                $period = new DatePeriod($start_date, $interval, $end_date);

                foreach ($period as $date) {
                    $day_start = $date->format('Y-m-d 00:00:00');
                    $day_end = $date->format('Y-m-d 23:59:59');

                    $entries_count = Participant::select(['id', 'ref', 'event_event_category_id', 'charity_id', 'corporate_id', 'status', 'created_at', 'updated_at'])
                        ->with([
                            'eventEventCategory.eventCategory:id,ref,name,slug',
                            'eventEventCategory.event:id,ref,name,slug',
                            'invoiceItem.invoice.upload',
                        ])
                        ->whereHas('eventEventCategory', function ($query) {
                            $query->whereHas('eventCategory', function ($query) {
                                $query->whereHas('site', function ($query) {
                                    $query->makingRequest();
                                });
                            });
                        })
                        ->when($from_date && $to_date, function ($query) use ($day_start, $day_end) {
                            $query->whereBetween('created_at', [$day_start, $day_end]);
                        })
                        ->orderByDesc('created_at')
                        ->count();

                    $dailyGraph[] = [
                        'x' => $date->format('Y-m-d'),
                        'y' => $entries_count
                    ];
                }
            }

            if (in_array($type, ['this_quarter', 'this_year'])) {
                $start_date = new DateTime($from_date);
                $end_date = new DateTime($to_date);

                $interval = new DateInterval('P1M');
                $period = new DatePeriod($start_date, $interval, $end_date);

                foreach ($period as $date) {
                    $month_start = $date->format('Y-m-01 00:00:00');
                    $month_end = $date->format('Y-m-t 23:59:59');

                    $entries_count = Participant::select(['id', 'ref', 'event_event_category_id', 'charity_id', 'corporate_id', 'status', 'created_at', 'updated_at'])
                        ->with([
                            'eventEventCategory.eventCategory:id,ref,name,slug',
                            'eventEventCategory.event:id,ref,name,slug',
                            'invoiceItem.invoice.upload',
                        ])
                        ->whereHas('eventEventCategory', function ($query) {
                            $query->whereHas('eventCategory', function ($query) {
                                $query->whereHas('site', function ($query) {
                                    $query->makingRequest();
                                });
                            });
                        })
                        ->when($from_date && $to_date, function ($query) use ($month_start, $month_end) {
                            $query->whereBetween('created_at', [$month_start, $month_end]);
                        })
                        ->orderByDesc('created_at')
                        ->count();

                    $dailyGraph[] = [
                        'x' => $date->format('F Y'),
                        'y' => $entries_count
                    ];
                }
            }

            if (in_array($type, ['today', 'yesterday'])) {
                $date = ($type == 'yesterday') ? new DateTime('-1 day') : new DateTime();

                $start_date = $date->format('Y-m-d 00:00:00');
                $end_date = $date->format('Y-m-d 23:59:59');

                $dailyGraph = [];

                for ($i = 0; $i < 24; $i++) {
                    $hour_start = $date->format('Y-m-d') . " $i:00:00";
                    $hour_end = $date->format('Y-m-d') . " $i:59:59";

                    $entries_count = Participant::select(['id', 'ref', 'event_event_category_id', 'charity_id', 'corporate_id', 'status', 'created_at', 'updated_at'])
                        ->with([
                            'eventEventCategory.eventCategory:id,ref,name,slug',
                            'eventEventCategory.event:id,ref,name,slug',
                            'invoiceItem.invoice.upload',
                        ])
                        ->whereHas('eventEventCategory', function ($query) {
                            $query->whereHas('eventCategory', function ($query) {
                                $query->whereHas('site', function ($query) {
                                    $query->makingRequest();
                                });
                            });
                        })
                        ->whereBetween('created_at', [$hour_start, $hour_end])
                        ->orderByDesc('created_at')
                        ->count();

                    $hour_label = DateTime::createFromFormat('H', $i)->format('g A');

                    $dailyGraph[] = [
                        'x' => $hour_label,
                        'y' => $entries_count,
                        'z' => $date->format('Y-m-d')
                    ];
                }
            }

            return response()->json([
                'message' => 'Entries Summary data retrieved successfully.',
                'code' => 200,
                'entries_count' => $entries,
                'graph_data' => $dailyGraph,
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ], $e->getCode());
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'An unexpected error occurred.'
            ], 500);
        }
    }

    public static function participantsSummary(): JsonResponse
    {
        try {
            $type = request('type', 'this_month');

            $validTypes = ['today', 'yesterday', 'this_week', 'this_month', 'this_quarter', 'this_year', 'custom'];
            if (!in_array($type, $validTypes)) {
                throw new \InvalidArgumentException('Invalid type. Supported types are: ' . implode(', ', $validTypes), 400);
            }

            $from_date = $to_date = null;

            switch ($type) {
                case 'today':
                    $from_date = now()->startOfDay()->format('Y-m-d H:i:s');
                    $to_date = now()->endOfDay()->format('Y-m-d H:i:s');
                    break;
                case 'yesterday':
                    $from_date = now()->subDay()->startOfDay()->format('Y-m-d H:i:s');
                    $to_date = now()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                    break;
                case 'this_week':
                    $from_date = now()->startOfWeek()->format('Y-m-d H:i:s');
                    $to_date = now()->endOfWeek()->format('Y-m-d H:i:s');
                    break;
                case 'this_month':
                    $from_date = now()->startOfMonth()->format('Y-m-d H:i:s');
                    $to_date = now()->endOfMonth()->format('Y-m-d H:i:s');
                    break;
                case 'custom':
                    $from_date = request('from_date');
                    $to_date = request('to_date');
                    if (!$from_date || !$to_date) {
                        throw new \InvalidArgumentException('Custom date range requires both from_date and to_date parameters.', 400);
                    }
                    $from_date = Carbon::parse($from_date)->startOfDay()->format('Y-m-d H:i:s');
                    $to_date = Carbon::parse($to_date)->endOfDay()->format('Y-m-d H:i:s');
                    break;
                case 'this_quarter':
                    $currentMonth = now()->month;
                    $currentYear = now()->year;
                    $quarter = ceil($currentMonth / 3);
                    $from_date = Carbon::create($currentYear, ($quarter - 1) * 3 + 1, 1)->startOfDay()->format('Y-m-d H:i:s');
                    $to_date = Carbon::create($currentYear, $quarter * 3, 1)->endOfMonth()->endOfDay()->format('Y-m-d H:i:s');
                    break;
                case 'this_year':
                    $from_date = Carbon::create(now()->year, 1, 1)->startOfDay()->format('Y-m-d H:i:s');
                    $to_date = Carbon::create(now()->year, 12, 31)->endOfDay()->format('Y-m-d H:i:s');
                    break;
            }
            $participantsQuery = DB::table('participants')
                ->select('user_id')
                ->distinct()
                ->join('event_event_category', 'event_event_category.id', '=', 'participants.event_event_category_id')
                ->join('event_categories', 'event_categories.id', '=', 'event_event_category.event_category_id')
                ->whereNull('participants.deleted_at')
                ->where('event_categories.site_id', clientSiteId());
            $participants = $participantsQuery->whereBetween('participants.created_at', [$from_date, $to_date]);
            $participants_count_data = $participants->count('user_id');

            $xAxis = [];
            $yAxis = [];


            if (in_array($type, ['this_week', 'this_month', 'custom'])) {
                $start_date = new DateTime($from_date);
                $end_date = new DateTime($to_date);
                $interval = new DateInterval('P1D');
                $period = new DatePeriod($start_date, $interval, $end_date);
                $all_user_ids = [];
                foreach ($period as $date) {
                    $day_start = $date->format('Y-m-d 00:00:00');
                    $day_end = $date->format('Y-m-d 23:59:59');
                    $participantsQuery1 = DB::table('participants')
                        ->select('user_id')
                        ->distinct()
                        ->join('event_event_category', 'event_event_category.id', '=', 'participants.event_event_category_id')
                        ->join('event_categories', 'event_categories.id', '=', 'event_event_category.event_category_id')
                        ->whereNull('participants.deleted_at')
                        ->where('event_categories.site_id', clientSiteId())
                        ->whereBetween('participants.created_at', [$day_start, $day_end]);

                    $day_user_ids = $participantsQuery1->pluck('user_id')->toArray();
                    $new_user_ids = array_diff($day_user_ids, $all_user_ids);
                    $all_user_ids = array_merge($all_user_ids, $new_user_ids);
                    $participant_count = count($new_user_ids);
                    $xAxis[] = $date->format('Y-m-d');
                    $yAxis[] = $participant_count;
                }
            }

            if (in_array($type, ['this_quarter', 'this_year'])) {
                $start_date = new DateTime($from_date);
                $end_date = new DateTime($to_date);
                $interval = new DateInterval('P1M');
                $period = new DatePeriod($start_date, $interval, $end_date->modify('+1 month'));
                $all_user_ids = [];
                foreach ($period as $date) {
                    $month_start = $date->format('Y-m-01 00:00:00');
                    $month_end = $date->format('Y-m-t 23:59:59');

                    $participantsQuery2 = DB::table('participants')
                        ->select('user_id')
                        ->distinct()
                        ->join('event_event_category', 'event_event_category.id', '=', 'participants.event_event_category_id')
                        ->join('event_categories', 'event_categories.id', '=', 'event_event_category.event_category_id')
                        ->whereNull('participants.deleted_at')
                        ->where('event_categories.site_id', clientSiteId())
                        ->whereBetween('participants.created_at', [$month_start, $month_end]);

                    $month_user_ids = $participantsQuery2->pluck('user_id')->toArray();
                    $new_user_ids = array_diff($month_user_ids, $all_user_ids);
                    $all_user_ids = array_merge($all_user_ids, $new_user_ids);
                    $participant_count = count($new_user_ids);
                    $xAxis[] = $date->format('F Y');
                    $yAxis[] = $participant_count;
                }
            }

            if (in_array($type, ['today', 'yesterday'])) {
                $date = ($type == 'yesterday') ? now()->subDay() : now();

                for ($i = 0; $i < 24; $i++) {
                    $hour_start = $date->format('Y-m-d') . " $i:00:00";
                    $hour_end = $date->format('Y-m-d') . " $i:59:59";
                    $participantsQuery3 = DB::table('participants')
                        ->select('user_id')
                        ->distinct()
                        ->join('event_event_category', 'event_event_category.id', '=', 'participants.event_event_category_id')
                        ->join('event_categories', 'event_categories.id', '=', 'event_event_category.event_category_id')
                        ->whereNull('participants.deleted_at')
                        ->where('event_categories.site_id', clientSiteId());
                    $participant_count = $participantsQuery3->whereBetween('participants.created_at', [$hour_start, $hour_end])->count('user_id');
                    $xAxis[] = DateTime::createFromFormat('H', $i)->format('g A');
                    $yAxis[] = $participant_count;
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Participants count retrieved successfully.',
                'code' => 200,
                'widgets_count' => $participants_count_data,
                'graph_data' => [
                    'z' => array_sum($yAxis),
                    'x' => $xAxis,
                    'y' => $yAxis,
                ]
            ], 200);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ], $e->getCode());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while retrieving participants count.',
                'code' => 500
            ], 500);
        }
    }
}
