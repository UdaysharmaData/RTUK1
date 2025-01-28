<?php

namespace App\Services\DataServices;

use App\Filters\PartnerChannelsOrderByFilter;
use App\Modules\Partner\Models\PartnerChannel;
use App\Modules\Partner\Requests\PartnerChannelListingQueryParamsRequest;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\DataServices\DataService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PartnerChannelDataService extends DataService implements DataServiceInterface
{

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
        return $this->getFilteredPartnerChannelsQuery($request);
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
     * @param  mixed  $request
     * @return \Illuminate\Database\Eloquent\Collection|Builder
     */
    public function getExportList(mixed $request): Builder|\Illuminate\Database\Eloquent\Collection
    {
        return $this->getFilteredQuery($request)->get();
    }

    /**
     * @param  string $ref
     * @return PartnerChannel
     */
    public function edit(string $ref): PartnerChannel
    {
        return PartnerChannel::with(['partner'])
            ->whereHas('partner', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            })->where('ref', $ref)
            ->firstOrFail();
    }

    /**
     * @param    $request
     * @return Builder
     */
    private function getFilteredAllQuery(Request $request): Builder
    {
        return PartnerChannel::select('id', 'ref', 'name', 'code', 'partner_id')
            ->whereHas('partner', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->makingRequest();
                });
            })->when(
                $request->filled('term'),
                fn ($query) => $query->where('name', 'like', "%{$request->term}%")
            )->orderBy('name');
    }

    /**
     * @param  PartnerChannelListingQueryParamsRequest  $request
     * @return Builder
     */
    private function getFilteredPartnerChannelsQuery(PartnerChannelListingQueryParamsRequest $request): Builder
    {
        return PartnerChannel::with(['partner'])
            ->whereHas('partner', function ($query) use ($request) {
                $query->whereHas('site', function ($query) {
                    $query->makingRequest();
                })->filterListBy(new PartnerChannelsOrderByFilter);
                if ($request->filled('partner')) {
                    $query->where('ref', $request->partner);
                }
            })->when(
                $request->filled('term'),
                fn ($query) => $query->where(function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->term . '%')
                        ->orWhere('code', 'like', '%' . $request->term . '%');
                })
            )->when(
                !$request->filled('order_by'), // Default Ordering
                fn ($query) => $query->orderBy('name')
            );
    }
}
