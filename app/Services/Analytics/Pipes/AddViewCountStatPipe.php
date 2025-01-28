<?php

namespace App\Services\Analytics\Pipes;

use Illuminate\Database\Eloquent\Builder;

class AddViewCountStatPipe extends StatPipe
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param \Closure $next
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    public function handle(Builder $builder, \Closure $next): Builder|\Illuminate\Database\Query\Builder
    {
        return $next($builder)
            ->withCount([
                'views as views_count',
                'views as views_previous_count' => $this->previousQuery(),
                'views as views_current_count' => $this->currentQuery()
            ]);
    }
}
