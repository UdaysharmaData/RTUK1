<?php

namespace App\Http\Controllers;

use App\Modules\Event\Models\Event;
use App\Modules\Participant\Models\Participant;
use App\Modules\User\Models\User;
use App\Traits\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StatsController extends Controller
{
    use Response;
    /**
     * Get Stats
     *
     * Get platform stats.
     *
     * @group Stats
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(): \Illuminate\Http\JsonResponse
    {
        try {
            // todo: scope with where clauses ????
            $stats = Cache::remember('app-stats', now()->addHour(), function () {
                return [
                    'users' => User::query()->count(),
                    'events' => Event::query()->count(),
                    'participants' => Participant::query()->count()
                ];
            });

            return $this->success('Platform Stats.', 200, [
                'stats' => $stats
            ]);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->error('An error occurred while trying to get stats.', 400);
        }
    }
}
