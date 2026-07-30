<?php

namespace App\Http\Middleware\Government;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureGovernmentAdministrator
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::guard('government')->user();
        $role = $user?->role()->first();

        abort_unless($role && in_array($role->code, config('government.allowed_roles'), true), 403);

        $user->setRelation('role', $role);
        $request->setUserResolver(fn (): User => $user);

        return $next($request);
    }
}
