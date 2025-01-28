<?php

namespace App\Services\Analytics\Pipes;

use Illuminate\Database\Eloquent\Builder;

class AddInteractionCountStatPipe extends StatPipe
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
                'interactions as interactions_count',
                'interactions as interactions_previous_count' => $this->previousQuery(),
                'interactions as interactions_current_count' => $this->currentQuery()
            ]);
    }
}
