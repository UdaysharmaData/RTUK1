<?php

namespace App\Http\Middleware;

use Log;
use Closure;
use App\Models\Site;
use App\Modules\Setting\Enums\SiteEnum;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class SiteMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $domain = null;

        if ($request->header('x-site')) {
            $domain = preg_replace('/http(s)?:\/\//i', '', $request->header('x-site'));
        } elseif(isset($_SERVER['HTTP_ORIGIN'])) {
            $domain = preg_replace('/http(s)?:\/\//i', '', $request->header('x-site'));
        } else {
            return response()->json([
                'status' => false,
                'message' => "Invalid site!"
            ], 403);
        }

        try {
            $site = Site::where('domain', $domain)->firstOrFail();

            if ($site && ! (SiteEnum::tryFrom($domain))) {
                return response()->json([
                    'status' => false,
                    'message' => "The site exists but it is not hardcoded under the SiteEnum file. Please update this file!"
                ], 403);
            }

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => "Invalid site!"
            ], 403);
        }

        return $next($request);
    }
}
