<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UpdateLastActivity
{
    private const THROTTLE_SECONDS = 60;

    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $cacheKey = "user_activity_throttled_{$userId}";

            if (! Cache::has($cacheKey)) {
                /** @var User $user */
                $user = Auth::user();
                $user->last_activity = now();
                $user->save();

                Cache::put($cacheKey, true, self::THROTTLE_SECONDS);
            }
        }

        return $next($request);
    }
}
