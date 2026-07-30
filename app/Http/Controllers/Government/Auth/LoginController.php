<?php

namespace App\Http\Controllers\Government\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Government\GovernmentLoginRequest;
use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_MINUTES = 15;

    public function create(): View
    {
        return view('government.auth.login');
    }

    public function store(GovernmentLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        if ($this->recentFailures($credentials['username']) >= self::MAX_ATTEMPTS) {
            return back()->withErrors([
                'username' => 'تم إيقاف محاولة الدخول مؤقتاً. حاول بعد '.self::LOCKOUT_MINUTES.' دقيقة.',
            ])->onlyInput('username');
        }

        $guard = Auth::guard('government');
        $authenticated = $guard->attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'is_active' => true,
        ]);
        $authorized = $authenticated && $this->isAuthorized($guard);

        LoginAttempt::query()->create([
            'username' => $this->attemptUsername($credentials['username']),
            'ip_address' => $request->ip() ?? 'unknown',
            'success' => $authorized,
            'created_at' => now(),
        ]);

        if (! $authorized) {
            $guard->logout();

            return back()->withErrors([
                'username' => 'بيانات الدخول غير صحيحة أو أن الحساب غير مخول للبوابة الحكومية.',
            ])->onlyInput('username');
        }

        $request->session()->regenerate();
        User::query()->whereKey($guard->id())->update(['last_login_at' => now()]);

        return redirect()->intended(route('government.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('government')->logout();
        $request->session()->migrate(true);
        $request->session()->regenerateToken();

        return to_route('government.login');
    }

    private function recentFailures(string $username): int
    {
        return LoginAttempt::query()
            ->where('username', $this->attemptUsername($username))
            ->where('success', false)
            ->where('created_at', '>', now()->subMinutes(self::LOCKOUT_MINUTES))
            ->count();
    }

    private function attemptUsername(string $username): string
    {
        return Str::limit('gov:'.$username, 100, '');
    }

    private function isAuthorized(StatefulGuard $guard): bool
    {
        /** @var User|null $user */
        $user = $guard->user()?->loadMissing('role');

        return $user !== null && in_array($user->role->code, config('government.allowed_roles'), true);
    }
}
