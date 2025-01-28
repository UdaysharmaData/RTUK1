<?php

namespace App\Filters;

use App\Contracts\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class Filters implements Filterable
{
    /**
     * @var Builder
     */
    protected Builder $builder;
    
    protected Request $request;

    public function __construct(Request $request=null)
    {
        $this->request = !empty($request) ? $request : request();

    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->getFilters() as $filter => $value) {
            if (method_exists($this, $filter = Str::camel($filter))) {
                $this->$filter($value);
            }
        }

        return $this->builder;
    }

    /**
     * @return array|string|null
     */
    protected function getFilters(): array|string|null
    {
        return array_filter($this->request->only($this->filters));
    }
}
