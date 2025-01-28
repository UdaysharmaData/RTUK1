<?php
namespace App\Console\Commands;
use Str;
use Carbon\Carbon;
use App\Models\Report;
use App\Exports\CsvExporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Modules\Setting\Models\Site;
use Illuminate\Support\Facades\Http;
use App\Mail\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use Illuminate\Http\Client\ConnectionException;
use App\Mail\LDTReportDataAttachmentMail;
use App\Traits\DownloadTrait;
use Exception;

class GenerateReportLDTSyncUp extends Command
{
    use DispatchesJobs,DownloadTrait;  
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:report-ldt-sync-up {site} {--start_date=} {--end_date=}';
    // start_data and end_date is optional parameters
    // Example :- php artisan generate:report-ldt-sync-up runthrough --start_date=2023-04-30 --end_date=2023-04-30
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Report LDT Sync Up';

    public function handle()
    {
      
       
        try {
            $site = Site::where('name', $this->argument('site'))
            ->orWhere('domain', $this->argument('site'))
            ->orWhere('code', $this->argument('site'))
            ->first();
            $startDate = $this->option('start_date');
            $endDate = $this->option('end_date');
          

        if (!$site) {
            $this->error('Site not found. Please check the site name, domain, or code.');
            return;
        }
        $query = ExternalEnquiry::select(
            'events.id as event_id',
            'events.status as status',
            'events.name as event_name',
            'event_categories.id as category_id',
            'event_categories.name as category_name',
            'external_enquiries.event_category_event_third_party_id as third_party_id',
            'event_category_event_third_party.external_id as third_party_external_id',
            'event_third_parties.external_id',
            'event_third_parties.occurrence_id',
            'event_event_category.start_date as start_date',
            'event_event_category.end_date as end_date',
        )
            ->join('event_category_event_third_party', 'external_enquiries.event_category_event_third_party_id', '=', 'event_category_event_third_party.id')
            ->join('event_third_parties', 'event_category_event_third_party.event_third_party_id', '=', 'event_third_parties.id')
            ->join('event_categories', 'event_category_event_third_party.event_category_id', '=', 'event_categories.id')
            ->join('event_event_category', 'event_category_event_third_party.event_category_id', '=', 'event_event_category.id')
            ->join('events', 'event_third_parties.event_id', '=', 'events.id')
            ->where('external_enquiries.site_id', $site->id);

            if ($startDate &&  $endDate) {
                $startDate = Carbon::parse($startDate);
                $endDate = Carbon::parse($endDate);

                if (!$startDate) {
                    throw new Exception('Invalid start date format. Please use YYYY-MM-DD.');
                }

                if (!$endDate) {
                    throw new Exception('Invalid end date format. Please use YYYY-MM-DD.');
                }
                $query->whereDate('event_event_category.start_date', '>=', $startDate);

                $query->whereDate('event_event_category.start_date', '<=', $endDate);
            }else{
                $query->where('events.status', 1);

            }
            
            // Add condition to check if events.deleted_at is NULL
            $query->whereNull('events.deleted_at');
          
            $externalEnquiriesData = $query->groupBy('external_enquiries.event_category_event_third_party_id')
            ->get();
        if ($externalEnquiriesData->isEmpty()) {
            $this->info('No external enquiries data found for site ' . $site->name);
            return;
        }
        $reports = [];
        foreach ($externalEnquiriesData as $externalEnquiryData) {
            if ($externalEnquiryData->third_party_external_id) {
            $event = $externalEnquiryData->event;
                $queryParams = [
                    'page' => [
                        'size' => 1
                    ]
                ];
                if ($externalEnquiryData->occurrence_id) {
                    $queryParams = [
                        ...$queryParams,
                        'eventOccurrenceId' => $externalEnquiryData->occurrence_id,
                    ];
                }
                $url = config('apiclient.ldt_endpoint') . "{$externalEnquiryData->third_party_external_id}/participants?" . http_build_query($queryParams);
                $response = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->withToken(config('apiclient.ldt_token'))
                    ->accept('application/json')
                    ->retry(1, 1, function ($exception, $request) use ($site) {
                        Log::channel($site->code . 'ldtfetch')->info('Retrying...');
                        return $exception instanceof ConnectionException;
                    })
                    ->get($url);
                $result = $response->json();
                if (isset($result['data']) && !empty($result['data'])) {
                    $convertedCountTillDate = ExternalEnquiry::where('event_id', $event->id)
                    ->where('event_category_event_third_party_id', $externalEnquiryData->third_party_id)
                    ->where('converted', 1)
                    ->where('created_at', '<', Carbon::now())
                    ->count();
                $convertedCountCurrent = ExternalEnquiry::where('event_id', $event->id)
                    ->where('event_category_event_third_party_id', $externalEnquiryData->third_party_id)
                    ->where('converted', 1)
                    ->where('created_at', '>=', Carbon::now())
                    ->count();
                $failedCountTillDate = ExternalEnquiry::where('event_id', $event->id)
                    ->where('event_category_event_third_party_id', $externalEnquiryData->third_party_id)
                    ->where('converted', 0)
                    ->where('created_at', '<', Carbon::now())
                    ->count();
                $failedCountCurrent = ExternalEnquiry::where('event_id', $event->id)
                    ->where('event_category_event_third_party_id', $externalEnquiryData->third_party_id)
                    ->where('converted', 0)
                    ->where('created_at', '>=', Carbon::now())
                    ->count();
                    $reports[] = [
                        'site_name' => $site->name,
                        'site_id' => $site->id,
                        'event_name' => $externalEnquiryData->event_name,
                        'event_id' => $externalEnquiryData->event_id,
                        'event_category_event_third_party_id' => $externalEnquiryData->third_party_id,   
                        'event_category_id' => $externalEnquiryData->category_id,
                        'event_category_name' => $externalEnquiryData->category_name,
                        'ldt_race_id' => $result['data'][0]['raceId'],
                        'ldt_occurrence_id' => $result['data'][0]['eventOccurrenceId'],
                        'total_converted_till_date' => $convertedCountTillDate,
                        'total_ldt_count' => $result['page']['totalResults'],
                        'total_converted_current' => $convertedCountCurrent,
                        'total_failed_current' => $failedCountCurrent,
                        'total_failed_till_date' => $failedCountTillDate,
                        'ldt_event_name' => $result['data'][0]['eventName'],
                    ];

                }
            }
        }
        $attachment = $this->upload(false, $site, $reports);

        Mail::site($site)->send((new LDTReportDataAttachmentMail($attachment['s3PathLink'], $site)));
        Report::insert($reports);
    } catch (Exception $e) {
        Log::channel()->error('Error handling report: ' . $e->getMessage());
        $this->error('Error handling report: ' . $e->getMessage());
    }
    }


    private function storeFile(array $data, string $fileName, array $headers): void
    {
        Excel::store(
            new CsvExporter($data),
            config('app.csvs_path') . '/' . $fileName,
            config('filesystems.default'),
            \Maatwebsite\Excel\Excel::CSV,
            $headers
        );
    }
    public function upload(bool $sendAsAttachment = false, $site, $reports): array|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = ['Content-Type' => 'text/csv'];
        $fileName = Str::ucfirst('LDT-DATA-REPORT') . '-' . date('Y-m-d_H-i-s') . '.csv';
        $path = config('app.csvs_path') . '/' . $fileName;

        $this->storeFile($reports, $fileName, $headers);
        return static::generateMinTypeForFile($path, true, null, true);
    }

}
