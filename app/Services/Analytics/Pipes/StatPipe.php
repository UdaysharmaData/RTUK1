<?php

namespace App\Services\Analytics\Pipes;

use Illuminate\Database\Eloquent\Builder;

class StatPipe
{
    /**
     * @var int|\Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    protected int $changePeriodInDays = 7;

    public function __construct()
    {
        $this->changePeriodInDays = config('app.analytics.change_period_in_days', 7);
    }

    /**
     * @return \Closure
     */
    protected function previousQuery(): \Closure
    {
        return function (Builder $query) {
            $query->where('created_at', '>=', now()->subDays($this->changePeriodInDays * 2))
                ->where('created_at', '<', now()->subDays($this->changePeriodInDays));
        };
    }

    /**
     * @return \Closure
     */
    protected function currentQuery(): \Closure
    {
        return function (Builder $query) {
            $query->where('created_at', '>=', now()->subDays($this->changePeriodInDays));
        };
    }
}
