<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_MINUTES = 15;

    public function create(): View|RedirectResponse
    {
        return Auth::check() ? $this->dashboardRedirect() : view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $recentFailures = LoginAttempt::query()
            ->where('username', $credentials['username'])
            ->where('success', false)
            ->where('created_at', '>', now()->subMinutes(self::LOCKOUT_MINUTES))
            ->count();

        if ($recentFailures >= self::MAX_ATTEMPTS) {
            return back()->withErrors([
                'username' => 'تم إيقاف الحساب مؤقتاً. حاول بعد '.self::LOCKOUT_MINUTES.' دقيقة.',
            ])->onlyInput('username');
        }

        $authenticated = Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'is_active' => true,
        ]);
        $authorized = $authenticated && $this->isIhsaUser();

        LoginAttempt::query()->create([
            'username' => $credentials['username'],
            'ip_address' => $request->ip() ?? 'unknown',
            'success' => $authorized,
            'created_at' => now(),
        ]);

        if (! $authorized) {
            Auth::logout();

            return back()->withErrors([
                'username' => 'اسم المستخدم أو كلمة المرور غير صحيحة، أو أن الحساب غير مخول لبوابة IHSA.',
            ])->onlyInput('username');
        }

        $request->session()->regenerate();
        User::query()->whereKey(Auth::id())->update(['last_login_at' => now()]);

        return $this->dashboardRedirect();
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function dashboardRedirect(): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user()->loadMissing('role');

        return redirect()->route($user->role->dashboard_route);
    }

    private function isIhsaUser(): bool
    {
        /** @var User|null $user */
        $user = Auth::user()?->loadMissing('role');

        return $user !== null && ! in_array($user->role->code, config('government.allowed_roles'), true);
    }
}
