<?php

namespace App\Services\DataServices;

use App\Jobs\ProcessDataServiceExport;
use App\Traits\PaginationTrait;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Filters\DeletedFilter;
use App\Filters\PartnersOrderByFilter;

use App\Modules\Partner\Models\Partner;
use App\Modules\Partner\Requests\PartnerAllQueryParamsRequest;
use App\Modules\Partner\Requests\PartnerListingQueryParamsRequest;

use App\Services\Reporting\PartnerStatistics;
use App\Services\ExportManager\FileExporterService;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\ExportManager\Formatters\PartnerExportableDataFormatter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Traits\SiteTrait;

class PartnerDataService extends DataService implements DataServiceInterface
{
    use Response,SiteTrait;

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
        
        if($request instanceof PartnerListingQueryParamsRequest) {
            return $this->getFilteredPartnersQuery($request);
        }else{
            return $this->getFilteredPartnersQueryExport($request);
        }
    }

    /**
     * param mixed  $request
     * @return Builder
     */
    private function getFilteredPartnersQueryExport(mixed $request): Builder
    {
        $partners = Partner::with(['socials', 'upload'])
            ->withCount('partnerChannels')
            ->filterListBy(new DeletedFilter($request))
            ->whereHas('site', function ($query) use($request){
                $query->where('id',$request->site_id);
            })->filterListBy(new PartnersOrderByFilter($request));

        if ($request->filled('term')) {
            $partners = $partners->where(function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->term . '%')
                    ->orWhere('code', 'like', '%' . $request->term . '%');
            });
        }

        $partners = $partners->when(
            !$request->filled('order_by'), // Default Ordering
            fn ($query) => $query->orderBy('name')
        );

        return $partners;
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
     * @return Partner
     */
    public function edit(string $ref): Partner
    {
        return Partner::with(['meta', 'socials', 'site', 'upload'])
            ->withCount('partnerChannels')
            ->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->where('ref', $ref)
            ->firstOrFail();
    }

    /**
     * @param  string $ref
     * @return Partner
     */
    public function show(string $ref): Partner
    {
        return Partner::with(['upload'])
            ->withCount('partnerChannels')
            ->whereHas('site', function ($query) {
                $query->makingRequest();
            })->where('ref', $ref)
            ->firstOrFail();
    }

    /**
     * @param  mixed  $request
     * @return \Illuminate\Database\Eloquent\Collection|Builder
     */
    public function getExportList(mixed $request): Builder|\Illuminate\Database\Eloquent\Collection
    {
        return $this->getFilteredQuery($request)->withOnly([])->get();
    }

    /**
     * @param mixed $request
     * @return array|JsonResponse|BinaryFileResponse|StreamedResponse
     * @throws ExportableDataMissingException
     */
    public function downloadCsv(mixed $request): array|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $site = static::getSite();
        ProcessDataServiceExport::dispatch(
            (new FileExporterService(
                $this,
                new PartnerExportableDataFormatter,
                'partners'
            )),
            $request,
            $request->user(),
            $site,
        );

        return $this->success('The exported file will be sent to your email shortly.');
    }

    /**
     * @param  PartnerAllQueryParamsRequest  $request
     * @return Builder
     */
    private function getFilteredAllQuery(PartnerAllQueryParamsRequest $request): Builder
    {
        return Partner::select('id', 'ref', 'name', 'slug', 'site_id')
            ->whereHas('site', function ($query) {
                $query->makingRequest();
            })->when(
                $request->filled('with') && $request->with == 'channels',
                fn ($query) => $query->with(['partnerChannels:id,ref,name,code,partner_id'])
            )->when(
                $request->filled('term'),
                fn ($query) => $query->where('name', 'like', "%{$request->term}%")
            )->orderBy('name');
    }

    /**
     * @param  PartnerListingQueryParamsRequest  $request
     * @return Builder
     */
    private function getFilteredPartnersQuery(PartnerListingQueryParamsRequest $request): Builder
    {
        $partners = Partner::with(['socials', 'upload'])
            ->withCount('partnerChannels')
            ->filterListBy(new DeletedFilter)
            ->whereHas('site', function ($query) {
                $query->makingRequest();
            })->filterListBy(new PartnersOrderByFilter);

        if ($request->filled('term')) {
            $partners = $partners->where(function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->term . '%')
                    ->orWhere('code', 'like', '%' . $request->term . '%');
            });
        }

        $partners = $partners->when(
            !$request->filled('order_by'), // Default Ordering
            fn ($query) => $query->orderBy('name')
        );

        return $partners;
    }

    /**
     * @return array
     */
    public function generateStatsSummary(): array
    {
        return PartnerStatistics::generateStatsSummary();
    }
}
