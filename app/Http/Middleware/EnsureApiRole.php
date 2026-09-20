<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يقصر مجموعة مسارات API على دور تطبيق بعينه (api.role:owner). يأتي بعد
 * auth:sanctum و api.active، فالمستخدم موجود ومفعّل وله دور.
 */
class EnsureApiRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()?->hasAppRole(...$roles)) {
            return response()->json(['message' => 'هذه الواجهة ليست لدورك.'], 403);
        }

        return $next($request);
    }
}
