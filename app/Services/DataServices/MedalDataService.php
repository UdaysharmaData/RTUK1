<?php

namespace App\Services\DataServices;

use App\Jobs\ProcessDataServiceExport;
use App\Services\Reporting\MedalStatistics;
use App\Traits\Response;
use Illuminate\Database\Eloquent\Builder;

use App\Models\Medal;
use App\Filters\DeletedFilter;
use App\Filters\DraftedFilter;
use App\Filters\MedalOrderByFilter;
use App\Modules\Event\Models\Event;
use App\Services\ExportManager\FileExporterService;
use App\Http\Requests\MedalListingQueryParamsRequest;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\ExportManager\Formatters\MedalExportableDataFormatter;

class MedalDataService implements DataServiceInterface
{
    use Response;

    public function getFilteredQuery(mixed $request): Builder
    {
        return $this->getFilteredMedalsQuery($request);
    }

    public function getPaginatedList(mixed $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $medals = $this->getFilteredMedalsQuery($request);

        return $medals = $medals->when(
            isset($request['per_page']),
            fn ($query) => $query->paginate((int) $request['per_page']),
            fn ($query) => $query->paginate(10)
        )->withQueryString();
    }

    public function getExportList(mixed $request): \Illuminate\Database\Eloquent\Collection|Builder
    {
        return $this->getFilteredMedalsQuery($request, ['medalable'])->get();
    }

    public function downloadCsv(mixed $request): array|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        ProcessDataServiceExport::dispatch(
            (new FileExporterService(
                $this,
                new MedalExportableDataFormatter,
                'medals'
            )),
            json_encode($request),
            $request->user()
        );

        return $this->success('The exported file will be sent to your email shortly.');

//        return (new FileExporterService(
//            $this,
//            new MedalExportableDataFormatter,
//            'medals'
//        ))->download($request);
    }

    public static function customSelectsQuery(?array $relationsToShow = null)
    {
        $defaultRelations =  [
            'medalable' => function ($query) {
                $query->withoutAppends()->constrain([
                    Event::class => fn ($q) => $q->withoutRelations(),
                ])->select('id', 'ref', 'name', 'slug');
            },
            'upload' => function ($query) {
                $query->select('*');
            },
            'site' => function ($query) {
                $query->select('id', 'ref', 'name');
            }
        ];

        $selectedRelations = $relationsToShow
            ? array_intersect_key($defaultRelations, array_flip($relationsToShow))
            : $defaultRelations;

        return  Medal::with($selectedRelations);
    }

    public function getMedalByRef($ref): Medal
    {
        return $this->customSelectsQuery()->filterBySite()->where('ref', $ref)->withDrafted()->firstOrFail();
    }

    private function getFilteredMedalsQuery(MedalListingQueryParamsRequest $request, $relations = ['medalable', 'upload'])
    {
        return  static::customSelectsQuery($relations)->filterBySite()
            ->when($request->filled('term'), function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->term}%");
            })->when($request->filled('event'), function ($query) use ($request) {
                $query->whereHasMorph('medalable', Event::class, function ($query) use ($request) {
                    $query->where('slug', $request->event);
                });
            })->when($request->filled('type'), function ($query) use ($request) {
                $query->where('type', $request->type);
            })->when($request->filled('category'), function ($query) use ($request) {
                $query->whereHasMorph('medalable', EventCategory::class, function ($query) use ($request) {
                    $query->where('slug', $request->category);
                });
            })->filterListBy(new MedalOrderByFilter)
            ->filterListBy(new DraftedFilter)
            ->filterListBy(new DeletedFilter)
            ->when(!$request->filled('order_by'), function ($query) {
                $query->orderBy('name');
            });
    }

    /**
     * @param $year
     * @param $period
     * @param $type
     * @return mixed
     */
    public function generateStatsSummary($year, $period, $type): mixed
    {
        return MedalStatistics::generateStatsSummary($year, $period, $type);
    }
}
