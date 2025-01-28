<?php

namespace App\Services\DataServices;

use App\Contracts\Redirectable;
use App\Enums\RedirectHardDeleteStatusEnum;
use App\Enums\RedirectSoftDeleteStatusEnum;
use App\Enums\RedirectTypeEnum;
use App\Filters\DeletedFilter;
use App\Filters\RedirectOrderByFilter;
use App\Models\Redirect;
use App\Services\DataServices\Contracts\DataServiceInterface;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RedirectDataService extends DataService implements DataServiceInterface
{
    public function __construct()
    {
        $this->builder = Redirect::query();
        $this->appendAnalyticsData = false;
    }

    /**
     * @param mixed $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder
    {
        $type = RedirectTypeEnum::tryFrom(request('type'))?->value;
        $softDeleteStatus = RedirectSoftDeleteStatusEnum::tryFrom(request('soft_delete'))?->value;
        $hardDeleteStatus = RedirectHardDeleteStatusEnum::tryFrom(request('hard_delete'))?->value;
        $parameters = array_filter(request()->query());
        $term = request('term');

        $query = Redirect::query()
            ->filterListBy(new DeletedFilter)
            ->filterListBy(new RedirectOrderByFilter);

        if (count($parameters) === 0) {
            $query = $query->latest();
        }

        return $query
            ->when($softDeleteStatus, fn (Builder $query) => $query->where('soft_delete', '=', $softDeleteStatus))
            ->when($hardDeleteStatus, fn (Builder $query) => $query->where('hard_delete', '=', $hardDeleteStatus))
            ->when($type, fn (Builder $query) => $query->where('type', '=', $type))
            ->when($term, $this->applySearchTermFilter($term));
    }

    /**
     * @param string|null $term
     * @return \Closure
     */
    private function applySearchTermFilter(string|null $term): \Closure
    {
        return function (Builder $query) use ($term) {
            if (isset($term)) {
                $query->where('target_url', 'LIKE', "%$term%")
                    ->orWhere('redirect_url', 'LIKE', "%$term%")
                    ->orWhere('model->name', 'LIKE', "%$term%");
            }
        };
    }

    /**
     * @param mixed $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedList(mixed $request): LengthAwarePaginator
    {
        return $this->paginate($this->getFilteredQuery($request));
    }

    /**
     * @param mixed $request
     * @return Builder[]|Collection
     */
    public function getExportList(mixed $request): Builder|Collection
    {
        return $this->getFilteredQuery($request)->get();
    }

    /**
     * @param string $ref
     * @return Model|Builder|\Illuminate\Database\Query\Builder
     */
    public function show(string $ref): \Illuminate\Database\Eloquent\Model|Builder|\Illuminate\Database\Query\Builder
    {
        return Redirect::query()
            ->where('ref', '=', $ref)
            ->firstOrFail();
    }

    /**
     * @param Redirectable $redirectable
     * @param array $data
     * @return Model
     */
    public function addRedirect(Redirectable $redirectable, array $data): Model
    {
        $targetUrl = $data['target_url'];
        $path = parse_url($targetUrl, PHP_URL_PATH);
        $data['target_path'] = $path;
        $data_return = Redirect::updateOrCreate(
            [
                'redirectable_type' => get_class($redirectable),
                'redirectable_id' => $redirectable->id,
            ],
            array_merge($data, [
                'model' => $redirectable
            ])
        );
        $this->redirectMongodbInsert($targetUrl);
        return $data_return;
    }

    public function redirectMongodbInsert($target_url)
    {
        $redirects = DB::table('redirects')
            ->where('target_url', $target_url)
            ->first();
        $aws_credentials = config('services.ses');
        $tableName =  $aws_credentials['table_name'];
        $client = new DynamoDbClient([
            'region'  => $aws_credentials['region'],
            'version' => 'latest',
        ]);
        $marshaler = new Marshaler();
        $data = [
            'id' =>  $redirects->id,
            'site_id' =>  clientSiteId(),
            'redirect_url' => $redirects->redirect_url,
            'target_url' => $redirects->target_url,
            'target_path' =>  $redirects->target_path,
            'http_code' => 301,
            'active' =>  $redirects->is_active,
            'created_at' => $redirects->created_at,
            'updated_at' => $redirects->updated_at,
        ];
        $client->putItem([
            'TableName' => $tableName,
            'Item' => $marshaler->marshalItem($data),
        ]);
    }

    /**
     * @param string $model
     * @param array $request
     * @return Model
     */
    public function addRedirectToDeletedEntity(string $model, array $request): Model
    {
        return Redirect::create(
            array_merge($request, [
                'redirectable_type' => $model,
                'redirectable_id' => $request['model']['id'],
            ])
        );
    }
}
