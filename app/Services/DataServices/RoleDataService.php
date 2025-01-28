<?php

namespace App\Services\DataServices;

use App\Filters\DeletedFilter;
use App\Filters\RoleOrderByFilter;
use App\Jobs\ProcessDataServiceExport;
use App\Modules\User\Models\Role;
use App\Services\ExportManager\Formatters\RoleExportableDataFormatter;
use App\Traits\PaginationTrait;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\ExportManager\FileExporterService;
use App\Traits\Response;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RoleDataService extends DataService implements DataServiceInterface
{
    use Response;

    /**
     * @param mixed $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder
    {
        $role = request('keyword');
        $parameters = array_filter(request()->query());
        $query = Role::query()
            ->siteOnly()
            ->filterListBy(new DeletedFilter)
            ->filterListBy(new RoleOrderByFilter);

        if (count($parameters) === 0) {
            $query = $query->latest();
        }

        return $query->when($role, $this->applyRoleFilter($role));
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
     * @return Collection|Builder
     */
    public function getExportList(mixed $request): Builder|Collection
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
                new RoleExportableDataFormatter,
                'roles'
            )),
            json_encode($request),
            $request->user()
        );

        return $this->success('The exported file will be sent to your email shortly.');

//        return (new FileExporterService(
//            $this,
//            new RoleExportableDataFormatter,
//            'roles'
//        ))->download($request);
    }

    /**
     * @param string|null $role
     * @return \Closure
     */
    public function applyRoleFilter(?string $role): \Closure
    {
        return function (Builder $query) use ($role) {
            if (is_string($role)) {
                return $query->where('name', 'LIKE', "%$role%")
                    ->orWhere('description', 'LIKE', "%$role%");
            }

            return $query;
        };
    }
}
