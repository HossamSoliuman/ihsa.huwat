<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * بعد auth:sanctum: الرمز صالح لكن الحساب قد يكون عُطّل أو نُزع دوره بعد
 * إصداره، فيُردّ 403 ويُلغى الرمز حتى لا يعود به.
 */
class EnsureApiAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user->active || $user->app_role_key === null) {
            $user->currentAccessToken()?->delete();

            return response()->json(['message' => 'هذا الحساب معطّل — راجع الإدارة.'], 403);
        }

        return $next($request);
    }
}
