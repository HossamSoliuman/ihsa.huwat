<?php

namespace App\Http\Controllers;

use App\Http\Requests\Counters\CounterApplicationRequest;
use App\Models\CounterApplication;
use App\Models\HiringRound;
use App\Services\Counters\CounterApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "اعمل عدّادًا": صفحة التقديم العامة على /counter/apply. يختار المتقدّم
 * المنطقة فالمحافظة فالميناء من الجولات التي تستقبل الطلبات، ويملأ بياناته،
 * ثم يوثّق جواله بالرمز على صفحة طلبه (/counter/apply/{token}) التي يتابع
 * منها قرار الشركة أو يسحب طلبه. لا حساب قبل الاعتماد.
 */
class CounterApplicationController extends Controller
{
    public function create(): View
    {
        $rounds = HiringRound::accepting()
            ->with(['port.governorate.region', 'company'])
            ->orderBy('closes_at')
            ->get();

        return view('counter-apply.create', [
            'rounds' => $rounds,
            // شجرة الاختيار: منطقة ← محافظة ← ميناء، من الجولات المفتوحة وحدها.
            'tree' => $rounds->map(fn (HiringRound $round) => [
                'round_id' => $round->id,
                'region' => $round->port?->governorate?->region?->name ?? 'أخرى',
                'governorate' => $round->port?->governorate?->name ?? 'أخرى',
                'port' => $round->port?->name,
                'company' => $round->company?->name,
                'title' => $round->title,
                'seats_left' => $round->seatsLeft(),
                'closes_at' => $round->closes_at->toDateString(),
                'notes' => $round->notes,
            ])->values(),
        ]);
    }

    public function store(CounterApplicationRequest $request, CounterApplicationService $service): RedirectResponse
    {
        $application = $service->submit($request->validated(), $request->ip());

        return redirect()->route('counter-apply.show', $application->token)
            ->with('status', 'استلمنا طلبك — أرسلنا رمز تحقق إلى جوالك، أدخله لإكمال التقديم.');
    }

    public function show(string $token): View
    {
        return view('counter-apply.show', ['application' => $this->application($token)]);
    }

    public function verify(Request $request, string $token, CounterApplicationService $service): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']], ['code.digits' => 'الرمز ستة أرقام.']);

        $service->verify($this->application($token), $data['code']);

        return back()->with('status', 'وُثّق جوالك ووصل طلبك إلى الشركة — يصلك قرارها برسالة نصية.');
    }

    public function resend(string $token, CounterApplicationService $service): RedirectResponse
    {
        $service->sendCode($this->application($token));

        return back()->with('status', 'أرسلنا رمزًا جديدًا إلى جوالك.');
    }

    public function withdraw(string $token, CounterApplicationService $service): RedirectResponse
    {
        $service->withdraw($this->application($token));

        return back()->with('status', 'سُحب طلبك.');
    }

    private function application(string $token): CounterApplication
    {
        return CounterApplication::with(['port', 'company', 'round'])->where('token', $token)->firstOrFail();
    }
}
