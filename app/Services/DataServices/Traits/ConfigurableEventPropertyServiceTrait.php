<?php

namespace App\Services\DataServices\Traits;

use App\Contracts\ConfigurableEventProperty;
use App\Exceptions\ConfigurableEventPropertyNotFoundException;
use App\Models\Upload;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\FileManager\Exceptions\UnableToOpenFileFromUrlException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait ConfigurableEventPropertyServiceTrait
{
    /**
     * @param mixed $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder
    {
        return $this->eventPropertyService->getFilteredQuery($request);
    }

    /**
     * @param  mixed  $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedList(mixed $request): LengthAwarePaginator
    {
        return $this->eventPropertyService->getPaginatedList($request);
    }

    /**
     * @param  mixed  $request
     * @return Collection|Builder
     */
    public function getExportList(mixed $request): Builder|Collection
    {
        return $this->eventPropertyService->getExportList($request);
    }

    /**
     * @param  mixed                                  $request
     * @return array|JsonResponse|BinaryFileResponse
     * @throws ExportableDataMissingException
     */
    public function downloadCsv(mixed $request): array|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->eventPropertyService->downloadCsv($request);
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function all(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->eventPropertyService->all();
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index($request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->eventPropertyService->index($request);
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function _index(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->eventPropertyService->_index();
    }

    /**
     * @param  ConfigurableEventProperty $property
     * @param  Request                   $request
     * @return array
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<\Illuminate\Database\Eloquent\Model>
     */
    public function events(ConfigurableEventProperty $property, Request $request): array
    {
        return $this->eventPropertyService->events($property, $request);
    }

    /**
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function sites(): \Illuminate\Database\Eloquent\Collection|array
    {
        return $this->eventPropertyService->sites();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Model|Builder
     * @throws UnableToOpenFileFromUrlException
     */
    public function store(Request $request): Builder|\Illuminate\Database\Eloquent\Model
    {
        return $this->eventPropertyService->store($request);
    }

    /**
     * @param string $propertyRef
     * @return Builder|\Illuminate\Database\Eloquent\Model
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<\Illuminate\Database\Eloquent\Model>
     */
    public function edit(string $propertyRef): \Illuminate\Database\Eloquent\Model|Builder
    {
        return $this->eventPropertyService->edit($propertyRef);
    }

    /**
     * @param Request $request
     * @param string $propertyRef
     * @return \Illuminate\Database\Eloquent\Model|Builder
     * @throws UnableToOpenFileFromUrlException
     */
    public function update(Request $request, string $propertyRef): Builder|\Illuminate\Database\Eloquent\Model
    {
        return $this->eventPropertyService->update($request, $propertyRef);
    }

    /**
     * @param string $propertySlug
     * @return mixed
     */
    public function _show($propertySlug): mixed
    {
        return $this->eventPropertyService->_show($propertySlug);
    }

    /**
     * @param string $propertyRef
     * @return Builder|\Illuminate\Database\Eloquent\Model
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<\Illuminate\Database\Eloquent\Model>
     */
    public function show(string $propertyRef): mixed
    {
        return $this->eventPropertyService->show($propertyRef);
    }

    /**
     * @param array $ids
     * @return mixed
     */
    public function markAsPublished(array $ids): mixed
    {
        return $this->eventPropertyService->markAsPublished($ids);
    }

    /**
     * @param array $ids
     * @return mixed
     */
    public function markAsDraft(array $ids): mixed
    {
        return $this->eventPropertyService->markAsDraft($ids);
    }

    /**
     * Delete one or many records
     *
     * @param array $ids
     * @return mixed
     */
    public function destroy(array $ids): mixed
    {
        return $this->eventPropertyService->destroy($ids);
    }

    /**
     * Restore one or many records
     *
     * @param  array $ids
     * @return mixed
     */
    public function restore(array $ids): mixed
    {
        return $this->eventPropertyService->restore($ids);
    }

    /**
     * Delete one or many records permanently
     *
     * @param array $ids
     * @return Collection
     */
    public function destroyPermanently(array $ids): Collection
    {
        return $this->eventPropertyService->destroyPermanently($ids);
    }

    /**
     * @param ConfigurableEventProperty $property
     * @param Upload $upload
     * @return mixed
     */
    public function removeImage(ConfigurableEventProperty $property, Upload $upload): mixed
    {
        return $this->eventPropertyService->removeImage($property, $upload);
    }

    /**
     * @param string $label
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     * @throws ConfigurableEventPropertyNotFoundException
     */
    public function export(string $label): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        return $this->eventPropertyService->export($label);
    }

    /**
     * @param array $relations
     * @return $this
     */
    public function addRelations(array $relations): static
    {
        $this->eventPropertyService->addRelations($relations);

        return $this;
    }

    /**
     * @param array $relations
     * @return $this
     */
    public function removeRelations(array $relations): static
    {
        $this->eventPropertyService->removeRelations($relations);

        return $this;
    }

    /**
     * @param array $relations
     * @return $this
     */
    public function setRelations(array $relations): static
    {
        $this->eventPropertyService->setRelations($relations);

        return $this;
    }
}
