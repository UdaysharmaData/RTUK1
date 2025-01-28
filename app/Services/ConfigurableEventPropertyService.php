<?php

namespace App\Services;

use App\Jobs\ProcessDataServiceExport;
use App\Modules\Event\Models\Serie;
use App\Modules\Event\Models\Sponsor;
use App\Services\DataServices\DataService;
use App\Services\DataServices\SerieDataService;
use App\Services\DataServices\SponsorDataService;
use App\Services\FileManager\Exceptions\UnableToOpenFileFromUrlException;
use App\Traits\Response;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use App\Traits\HelperTrait;
use App\Traits\DownloadTrait;
use App\Traits\PaginationTrait;
use App\Traits\CustomPaginationTrait;

use App\Filters\FaqsFilter;
use App\Filters\DeletedFilter;
use App\Filters\EventPropertyServicesOrderByFilter;

use App\Repositories\FaqRepository;
use App\Contracts\ConfigurableEventProperty;
use App\Services\ExportManager\FileExporterService;
use App\Services\Analytics\Events\AnalyticsViewEvent;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\ExportManager\Formatters\ConfigurableEventPropertyExportableDataFormatter;
use App\Exceptions\ConfigurableEventPropertyNotFoundException;

use App\Enums\EventStateEnum;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use App\Filters\DraftedFilter;
use App\Models\City;
use App\Models\Venue;
use App\Models\Region;
use App\Models\Upload;
use App\Modules\Event\Models\EventCategory;

use App\Modules\Setting\Models\Site;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\CityDataService;
use App\Services\DataServices\VenueDataService;
use App\Services\DataServices\RegionDataService;
use App\Services\DataServices\EventCategoryDataService;
use App\Services\DataServices\GlobalSearchDataService;
use App\Services\FileManager\Traits\UploadModelTrait;
use App\Modules\Event\Models\Event;

class ConfigurableEventPropertyService extends DataService implements DataServiceInterface
{
    use HelperTrait,UploadModelTrait, DownloadTrait, CustomPaginationTrait, Response;

    protected FaqRepository $faqRepository;

    public function __construct(
        protected ?Builder $builder,
        protected array    $relations = ['site', 'meta', 'image', 'gallery']
    ) {
        $this->faqRepository = new FaqRepository();
        $this->appendAnalyticsData = false;
    }

    public function getFilteredQuery(mixed $request): Builder
    {
        $term = request('term');
        $city = request('city');
        $region = request('region');
        $country = request('country');
        $orderBy = request('order_by');

        $this->builder = $this->filterByEntity($country, $region, $city);

        return $this->builder
            ->with($this->relations)
            ->filterListBy(new FaqsFilter)
            ->filterListBy(new DeletedFilter)
            ->filterListBy(new DraftedFilter)
            ->filterListBy(new EventPropertyServicesOrderByFilter)
            ->withCount('events')
            ->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->when($term, fn ($query) => $query->where('name', 'like', "%$term%"))
            ->when(
                !$orderBy, // Default Ordering
                fn ($query) => $query->orderBy('name')
            );
    }

    public function getPaginatedList(mixed $request): LengthAwarePaginator
    {
       $data = $this->paginate($this->getBuilderWithAnalytics($this->getFilteredQuery($request)))->through(function ($property) {
            if ($property->draft_url) {
                $property->append('draft_url');
            }
            return $property;
        });

        return $data;
    }

    public function getExportList(mixed $request): Builder|Collection
    {
        $query = $this->getFilteredQuery($request);

        return $query->get();
    }

    public function downloadCsv(mixed $request): BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
    {
        ProcessDataServiceExport::dispatch(
            (new FileExporterService(
                $this,
                new ConfigurableEventPropertyExportableDataFormatter,
                $this->builder->getModel()->getTable()
            )),
            json_encode($request),
            $request->user()
        );

        return $this->success('The exported file will be sent to your email shortly.');

//        return (new FileExporterService(
//            $this,
//            new ConfigurableEventPropertyExportableDataFormatter,
//            $this->builder->getModel()->getTable()
//        ))->download($request);
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function all(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $term = request('term');
        $city = request('city');
        $region = request('region');
        $country = request('country');

        $this->builder = $this->filterByEntity($country, $region, $city);

        if ($this->builder->getModel() instanceof Venue) {
            $this->builder->select('id', 'ref', 'name', 'slug', 'city_id');
        }

        if ($this->builder->getModel() instanceof City) {
            $this->builder->select('id', 'ref', 'name', 'slug', 'region_id');
        }

        return static::paginate($this->builder
            ->withoutAppends()
            ->with($this->relations)
            ->whereHas('site', fn ($query) => $query->makingRequest())
            ->when($term, fn ($query) => $query->where('name', 'LIKE', "%$term%"))
            ->orderBy('name'));
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function _index(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $term = request('term');
        $popular = request('popular');
        $city = request('city');
        $region = request('region');
        $country = request('country');

        $this->builder = $this->filterByEntity($country, $region, $city);

        return static::paginate($this->builder
            ->withoutAppends($this)
            ->with($this->relations)
            ->withCount([
                'events as active_events_count' => function ($query) {
                    $query->state(EventStateEnum::Live)->partnerEvent(Event::ACTIVE);
                }
            ])->whereHas('site', fn ($query) => $query->makingRequest())
            ->when($term, fn ($query) => $query->where('name', 'LIKE', "%{$term}%"))
            ->when($popular, fn ($query) => $query->orderByDesc("active_events_count"))
            ->orderBy('name'));
    }

    /**
     * @param  Request                                                $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->getPaginatedList($request);
    }

    /**
     *  get details of a property for the client side
     *
     * @param  string $propertySlug
     * @return mixed
     */
    public function _show(string $propertySlug): mixed
    {
        $entity = null;
        $exceptions = [];

        if (request()->venue && $this->builder->getModel() instanceof Venue) {
            $entity = request()->venue;
            $exceptions = [...$exceptions, 'venue'];
        }

        if (request()->city && $this->builder->getModel() instanceof City) {
            $entity = request()->city;
            $exceptions = [...$exceptions, 'city'];
        }

        if (request()->region && $this->builder->getModel() instanceof Region) {
            $entity = request()->region;
            $exceptions = [...$exceptions, 'region'];
        }

        if (request()->category && $this->builder->getModel() instanceof EventCategory) {
            $entity = request()->category;
            $exceptions = [...$exceptions, 'category'];
        }

        $property = $this->builder
            ->withoutAppends()
            ->with(['image', 'gallery', 'meta', 'faqs:id,faqsable_id,faqsable_type,section,description', 'faqs.faqDetails:id,faq_id,question,answer,view_more_link', 'faqs.faqDetails.uploads'])->withCount(['events as active_events_count' => function ($query) {
                $query->state(EventStateEnum::Live)->partnerEvent(Event::ACTIVE);
            }])->whereHas('site', function ($query) {
                $query->makingRequest();
            })->when(request()->draft, fn ($query) => $query->onlyDrafted())
            ->when(
                $entity,
                fn ($query) => $query->where('ref', $entity),
                fn ($query) => $query->where('slug', $propertySlug)
            )->firstOrFail();

        AnalyticsViewEvent::dispatch($property);

        return $property;
    }

    /**
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function sites(): \Illuminate\Database\Eloquent\Collection|array
    {
        return Site::select('id', 'ref', 'domain', 'name')
            ->hasAccess()
            ->makingRequest()
            ->get();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Model|Builder
     * @throws UnableToOpenFileFromUrlException
     */
    public function store(Request $request): Builder|\Illuminate\Database\Eloquent\Model
    {
        $configurableEventProperty = $this->builder->newModelInstance();
        $configurableEventProperty->fill($request->toArray());
        $configurableEventProperty->save();

        if ($request->image) { // Save the region's image
            $this->attachSingleUploadToModel($configurableEventProperty, $request->image);
        }

        if ($request->filled('gallery')) { // Save the models gallery
            $this->attachMultipleUploadsToModel($configurableEventProperty, $request->gallery, UploadUseAsEnum::Gallery);
        }

        $this->saveMetaData($request, $configurableEventProperty); // Save meta data

        if ($request->filled('faqs')) {
            $this->faqRepository->store($request->all(), $configurableEventProperty);
        }

        return $configurableEventProperty->load($this->relations);
    }

    /**
     * @param string $propertyRef
     * @return Builder|\Illuminate\Database\Eloquent\Model
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<\Illuminate\Database\Eloquent\Model>
     */
    public function edit(string $propertyRef): \Illuminate\Database\Eloquent\Model|Builder
    {
        $model = $this->getBuilderWithAnalytics()
            ->with($this->relations)
            ->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })
            ->where('ref', $propertyRef)
            ->withDrafted()
            ->firstOrFail();

        if ($model->draft_url) {
            $model->append('draft_url');
        }

        return $this->modelWithAppendedAnalyticsAttribute($model);
    }

    /**
     * @param Request $request
     * @param string $propertyRef
     * @return \Illuminate\Database\Eloquent\Model|Builder
     * @throws UnableToOpenFileFromUrlException
     */
    public function update(Request $request, string $propertyRef): Builder|\Illuminate\Database\Eloquent\Model
    {
        $_property = $this->builder
            ->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->where('ref', $propertyRef)
            ->withDrafted()
            ->firstOrFail();

        $property = tap($_property)->update($request->toArray());

        if ($request->filled('image')) { // Save the region's image
            $this->attachSingleUploadToModel($property, $request->image);
        }

        if ($request->filled('gallery')) { // Save the models gallery
            $this->attachMultipleUploadsToModel($property, $request->gallery, UploadUseAsEnum::Gallery);
        }

        $this->saveMetaData($request, $property); // Save meta data

        if ($request->filled('faqs')) {
            $this->faqRepository->update($request->all(), $property);
        }

        $this->clearDataService($property->getModel()); // Clear the data service cache

        return $this->modelWithAppendedAnalyticsAttribute($property->load($this->relations));
    }

    /**
     * @param string $propertyRef
     * @return Builder|\Illuminate\Database\Eloquent\Model
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<\Illuminate\Database\Eloquent\Model>
     */
    public function show(string $propertyRef): mixed
    {
        return $this->builder->with($this->relations)
            ->withCount('events')
            ->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->where('ref', $propertyRef)
            ->firstOrFail();
    }

    /**
     * @param array $ids
     * @return Builder|\Illuminate\Database\Eloquent\Model
     */
    public function markAsPublished(array $ids)
    {
        $properties = $this->builder
            ->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->whereIntegerInRaw('id', $ids)
            ->onlyDrafted()
            ->markAsPublished();

        $this->clearDataService($this->builder->getModel()); // Clear the data service cache

        return $properties;
    }

    /**
     * @param array $ids
     * @return mixed
     */
    public function markAsDraft(array $ids): mixed
    {
        $properties = $this->builder
            ->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->whereIntegerInRaw('id', $ids)
            ->markAsDraft();

        $this->clearDataService($this->builder->getModel()); // Clear the data service cache

        return $properties;
    }

    /**
     * Delete one or many records
     *
     * @param array $ids
     * @return mixed
     */
    public function destroy(array $ids): mixed
    {
        $properties = $this->builder
            ->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->withDrafted()
            ->whereIntegerInRaw('id', $ids)
            ->delete();

        $this->clearDataService($this->builder->getModel()); // Clear the data service cache

        return $properties;
    }

    /**
     * Restore one or many records
     *
     * @param  array $ids
     * @return mixed
     */
    public function restore(array $ids): mixed
    {
        $properties = $this->builder
            ->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->onlyTrashed()
            ->withDrafted()
            ->whereIntegerInRaw('id', $ids)
            ->restore();

        $this->clearDataService($this->builder->getModel()); // Clear the data service cache

        return $properties;
    }

    /**
     * Delete one or many records permanently
     *
     * @param array $ids
     * @return Collection
     */
    public function destroyPermanently(array $ids): Collection
    {
        $properties = $this->builder
            ->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->whereIntegerInRaw('id', $ids)
            ->withDrafted()
             ->onlyTrashed()->get()->each(function ($property) {
                $property->forceDelete();
            });

        return $properties;
    }

    /**
     * @param ConfigurableEventProperty $property
     * @param Upload $upload
     * @return mixed
     * @throws Exception
     */
    public function removeImage(ConfigurableEventProperty $property, Upload $upload): mixed
    {
        $_property = $this->builder
            ->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->whereId($property->id)
            ->withDrafted()
            ->firstOrFail();
        
        $this->detachUpload($property, $upload->ref);

        return $_property;
    }

    /**
     * @param array $relations
     * @return $this
     */
    public function addRelations(array $relations): static
    {
        $this->relations = array_merge($this->relations, $relations);

        return $this;
    }

    /**
     * @param array $relations
     * @return $this
     */
    public function removeRelations(array $relations): static
    {
        $this->relations = array_diff($this->relations, $relations);

        return $this;
    }

    public function setRelations(array $relations)
    {
        $this->relations = $relations;

        return $this;
    }

    /**
     *  Clear data service based on model instance
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    private function clearDataService(\Illuminate\Database\Eloquent\Model $model): void
    {
        if ($model instanceof Venue) {
            CacheDataManager::flushAllCachedServiceListings(new VenueDataService());
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
        }

        if ($model instanceof City) {
            CacheDataManager::flushAllCachedServiceListings(new CityDataService());
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
        }

        if ($model instanceof Region) {
            CacheDataManager::flushAllCachedServiceListings(new RegionDataService());
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
        }

        if ($model instanceof EventCategory) {
            CacheDataManager::flushAllCachedServiceListings(new EventCategoryDataService());
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
        }

        if ($model instanceof Sponsor) {
            CacheDataManager::flushAllCachedServiceListings(new SponsorDataService());
        }

        if ($model instanceof Serie) {
            CacheDataManager::flushAllCachedServiceListings(new SerieDataService());
        }
    }

    /**
     * Filter the query based on the entity
     *
     * @param string|null $country
     * @param string|null $region
     * @param string|null $city
     * @return Builder
     */
    private function filterByEntity(?string $country=null, ?string $region=null, ?string $city=null): Builder
    {
        if ($this->builder->getModel() instanceof Venue) {
            $this->builder->when(
                $country,
                fn ($query) => $query->whereHas('city', function ($query) use ($country) {
                    $query->whereHas('region', function ($query) use ($country) {
                        $query->where('country', $country);
                    });
                })
            )->when(
                $region,
                fn ($query) => $query->whereHas('city', function ($query) use ($region) {
                    $query->whereHas('region', function ($query) use ($region) {
                        // $query->where('ref', $region);
                        $regionArray = explode(',', $region); // Convert the string into an array
                        $query->whereIn('ref', $regionArray);
                    });
                })
            )->when(
                $city,
                fn ($query) => $query->whereHas('city', function ($query) use ($city) {
                    //$query->where('ref', $city);
                    $cityArray = explode(',', $city); // Convert the string into an array
                    $query->whereIn('ref', $cityArray);
                })
            );
        }

        if ($this->builder->getModel() instanceof City) {
            $this->builder->when(
                $country,
                fn ($query) => $query->whereHas('region', function ($query) use ($country) {
                    $query->where('country', $country);
                })
            )->when(
                $region,
                fn ($query) => $query->whereHas('region', function ($query) use ($region) {
                    //$query->where('ref', $region);
                    $regionArray = explode(',', $region); // Convert the string into an array
                    $query->whereIn('ref', $regionArray);
                })
            );
        }

        if ($this->builder->getModel() instanceof Region) {
            $this->builder->when(
                $country,
                fn ($query) => $query->where('country', $country)
            );
        }

        return $this->builder;
    }
}
