<?php

namespace App\Services\DataServices;

use App\Jobs\ProcessDataServiceExport;
use App\Services\Reporting\EntryStatistics;
use App\Traits\Response;
use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Filters\YearFilter;
use App\Filters\PeriodFilter;
use App\Filters\EntriesOrderByFilter;

use App\Modules\Participant\Models\Participant;
use App\Services\ExportManager\FileExporterService;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Modules\Participant\Requests\EntryListingQueryParamsRequest;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\ExportManager\Formatters\ParticipantExportableDataFormatter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EntryDataService extends DataService implements DataServiceInterface
{
    use Response;

    /**
     * @param  mixed  $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder
    {
        return $this->getFilteredEntriessQuery($request);
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
     * @param  string       $participant
     * @return Participant
     */
    public function edit(string $participant): Participant
    {
        return Participant::with(['charity:id,ref,name,slug', 'eventEventCategory.event.eventCustomFields', 'eventEventCategory.eventCategory:id,ref,name,slug', 'eventPage', 'invoiceItem.invoice.upload', 'participantCustomFields.eventCustomField', 'participantExtra:id,ref,participant_id,first_name,last_name', 'user.profile.participantProfile'])
            ->where('ref', $participant)
            ->filterByAccess()
            ->whereHas('eventEventCategory.eventCategory', function ($query) {
                $query->whereHas('site', function ($q) {
                    $q->makingRequest();
                });
            })->firstOrFail();
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
                new ParticipantExportableDataFormatter,
                'entries'
            )),
            json_encode($request),
            $request->user()
        );

        return $this->success('The exported file will be sent to your email shortly.');

//        return (new FileExporterService(
//            $this,
//            new ParticipantExportableDataFormatter,
//            'entries'
//        ))->download($request);
    }

    /**
     * @param  EntryListingQueryParamsRequest  $request
     * @return Builder
     */
    private function getFilteredEntriessQuery(EntryListingQueryParamsRequest $request): Builder
    {
        $entries = Participant::select(['id', 'ref', 'event_event_category_id', 'charity_id', 'corporate_id', 'status', 'created_at', 'updated_at'])
            ->with([
                'eventEventCategory.eventCategory:id,ref,name,slug',
                'eventEventCategory.event:id,ref,name,slug',
                'invoiceItem.invoice.upload',
                'charity:id,ref,name,slug',
                'participantExtra:id,ref,participant_id,first_name,last_name',
                'user:id,ref,email,first_name,last_name,phone',
                'user.profile:id,user_id,gender,occupation,nationality,dob,postcode,state,city,region,address,country',
            ])->filterListBy(new EntriesOrderByFilter)
            ->filterListBy(new PeriodFilter)
            ->filterListBy(new YearFilter)
            ->whereHas('eventEventCategory', function ($query) use ($request) {
            $query->whereHas('eventCategory', function ($query) use ($request) {
                $query->whereHas('site', function ($query) {
                    $query->makingRequest();
                })->when($request->filled('category'), fn ($query) => $query->where('ref', $request->category));
            })->when($request->filled('term'), fn ($query) => $query->where(function ($query) use ($request) {
                $query->whereHas('event', function ($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->term.'%');
                })->orWhereHas('eventCategory', function ($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->term.'%');
                });
            }));
        })->where('user_id', Auth::user()->id)
        ->when($request->filled('status'),
            fn ($query) => $query->where('status', $request->status)
        )->when(! $request->filled('order_by'), // Default Ordering
            fn($query) => $query->orderByDesc('created_at')
        );

        return $entries;
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
        return EntryStatistics::generateStatsSummary($type, $year, $status, $category, $period);
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
        return EntryStatistics::generateYearGraphData($type, $year, $status, $category, $period);
    }
}
