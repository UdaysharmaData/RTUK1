<?php

namespace App\Services\GlobalSearchService;

use Illuminate\Database\Eloquent\Model;

use App\Contracts\CanHaveManySearchHistories;
use App\Services\GlobalSearchService\Exceptions\InvalidSearchableModel;

class ModelSearchAspect
{
    protected $model;

    /**
     * add extra condition to the search query
     *
     * @var \Closure
     */
    protected $searchCondition;

    public function __construct(string $model, \Closure $searchCondition = null)
    {
        if (!is_subclass_of($model, Model::class)) { // $model is not a subclass of Model
            throw InvalidSearchableModel::notAModel($model);
        }

        if (!method_exists($model, 'search')) { // $model does not have a search method
            throw InvalidSearchableModel::modelDoesNotImplementSearchable($model);
        }

        if (!in_array(CanHaveManySearchHistories::class, class_implements($model))) { // $model does not implement CanHaveManySearchHistories
            throw InvalidSearchableModel::modelDoesNotImplementCanHaveManySearchHistories($model);
        }

        $this->model = $model;

        $this->searchCondition = $searchCondition;
    }

    /**
     * get the table name
     *
     * @return string
     */
    public function getType(): string
    {
        $model = new $this->model();

        return $model->getTable();
    }

    /**
     * get the model name
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * get paginated results of the search
     *
     * @param  string $term
     * @param  int $limit
     * @return mixed
     */
    public function getPaginatedResults(string $term, int $limit): mixed
    {
        $query = ($this->model)::search($term, $this->searchCondition);

        return $query->paginate($limit);
    }

    /**
     * get limited results of the search
     *
     * @param  string $term
     * @param  int $limit
     * @return mixed
     */
    public function getLimitedResults(string $term, int $limit)
    {
        $query = ($this->model)::search($term, $this->searchCondition);

        return $query->take($limit)->get();
    }
}
