<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * تسجيل الدخول إلى بوابة المعلومات.
 *
 * البوابة وحدها محميّة: لوحة الوزارة عرضٌ وتحليل، أمّا هذه فتحرّر البيانات
 * الأساسية وتكتب في سجل العمليات — فلا تُفتح إلا لمستخدم معروف يُنسب إليه ما
 * يكتبه (انظر AdminResourceController::log).
 *
 * الجلسة على النطاق الأب (SESSION_DOMAIN=.hawat.sa) فيبقى الدخول قائمًا بين
 * المضيفَين، ولذلك يُعاد توليد معرّفها بعد الدخول وتُبطَل كاملةً عند الخروج.
 */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // رسالة واحدة للحالتين: وجود البريد من عدمه لا يُستدلّ عليه من الردّ.
            throw ValidationException::withMessages([
                'email' => 'بيانات الدخول غير صحيحة.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
