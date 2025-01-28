<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Enums\SiteUserStatus;
use App\Modules\User\Models\User;

class UserStatusIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
//        if (auth()->check()) {
//            $userSitesQuery = $request->user()->sites()->where('site_id', '=', clientSiteId());
//
//            if ($userSitesQuery->doesntExist()) {
//                $userSitesQuery->attach([clientSiteId()]);
//                $check = true;
//            } else $check = $request->user()->sites()->where('site_user.status', '=', SiteUserStatus::Active->value)->exists();
//        } elseif (($request->route()?->getName() === 'login') && $request->filled('email')) {
//            $userQuery = User::query()
//                ->where('email', '=', $request->get('email'));
//
//            if ($userQuery->exists()) {
//                $userSitesQuery = $userQuery->clone()->whereHas('sites', function ($query) use ($request) {
//                    $query->where('site_id', '=', clientSiteId());
//                });
//
//                if ($userSitesQuery->doesntExist()) {
//                    $userQuery->first()->sites()->attach([clientSiteId()]);
//                    $check = true;
//                } else $check = $userQuery->first()->sites()->where('site_user.status', '=', SiteUserStatus::Active->value)->exists();
//            } else $check = true;
//        } else $check = true;
//
//        if ($check) return $next($request);
//
//        abort(403, 'Your account is currently restricted. Please contact the admin to have this resolved.');

        if ($user = auth()->user()) {
            $check = $this->userStatusIsActive($user);
        } elseif (($request->route()?->getName() === 'login') && $request->filled('email')) {
            $user = User::query()
                ->withoutEagerLoads()
                ->where('email', '=', $request->get('email'))
                ->first();

            if (! is_null($user)) {
                $check = $this->userStatusIsActive($user);
            } else $check = true;
        } else $check = true;

        if ($check) return $next($request);

        abort(403, 'Your account is currently restricted. Please contact the admin to have this resolved.');
    }

    /**
     * @param Authenticatable|Model $user
     * @return bool
     */
    private function userStatusIsActive(Authenticatable|Model $user): bool
    {
        $userCurrentSite = $user->sites()
            ->where('site_id', '=', clientSiteId())
            ->first();

        if (is_null($userCurrentSite)) {
            $user->sites()->syncWithoutDetaching([clientSiteId()]);

            $check = true;
        } else $check = $userCurrentSite->pivot?->status?->value === SiteUserStatus::Active->value;

        return $check;
    }
}
