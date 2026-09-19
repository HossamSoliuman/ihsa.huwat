<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * تسجيل الدخول إلى لوحة الإدارة على /admin.
 *
 * حسابات التطبيق تدخل بجوالها كما في التطبيق نفسه، والمدير العام ببريده كما
 * اعتاد في بوابة المعلومات — فالحقل واحد ("الجوال أو البريد") ويُفسَّر حسب
 * شكله. الجلسة نفسها تخدم بوابة المعلومات على مضيفها (SESSION_DOMAIN).
 */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('panel.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $credentials = self::credentials($input['identifier']) + ['password' => $input['password']];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // رسالة واحدة للحالتين: وجود الحساب من عدمه لا يُستدلّ عليه من الردّ.
            throw ValidationException::withMessages([
                'identifier' => 'بيانات الدخول غير صحيحة.',
            ]);
        }

        $request->session()->regenerate();

        $request->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('panel.home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('panel.login');
    }

    /**
     * ما فيه @ بريد، وما عداه جوال يُطبَّع إلى صيغته الموحّدة.
     *
     * @return array{email: string}|array{phone: string}
     */
    public static function credentials(string $identifier): array
    {
        if (str_contains($identifier, '@')) {
            return ['email' => mb_strtolower(trim($identifier))];
        }

        return ['phone' => User::normalizePhone($identifier) ?? $identifier];
    }
}
