<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * باب لوحة الإدارة: لا يمرّ إلا حساب له دور في التطبيق وما زال مفعّلًا.
 *
 * موظف الوزارة الذي لا دور تطبيق له يُردّ بـ 403 لا بصفحة الدخول — هو داخلٌ
 * أصلًا لكن اللوحة ليست له. والحساب المعطّل يُخرَج من جلسته ويُعاد إلى الدخول
 * برسالة، حتى لا يبقى داخلًا بجلسة سبقت تعطيله.
 *
 * تُقيَّد المجموعة بأدوار بعينها بتمريرها: panel:super_admin,owner.
 */
class EnsurePanelAccess
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user->active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('panel.login')->withErrors(['identifier' => 'هذا الحساب معطّل.']);
        }

        if ($user->app_role_key === null) {
            abort(403, 'ليس لهذا الحساب دور في لوحة الإدارة.');
        }

        if ($roles !== [] && ! $user->hasAppRole(...$roles)) {
            abort(403, 'هذه الصفحة ليست من صلاحيات دورك.');
        }

        return $next($request);
    }
}
