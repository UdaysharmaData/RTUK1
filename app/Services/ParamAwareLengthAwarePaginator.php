<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;

class ParamAwareLengthAwarePaginator extends LengthAwarePaginator
{
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'query_params' => count($params = array_filter(request()->query(),
                function ($var) {
                    return isset($var);
                })
            ) > 0
                ? $params
                : null
        ]);
    }
}
