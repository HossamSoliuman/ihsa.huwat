<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\RegistrationRequest;
use App\Models\Role;
use App\Services\Registration\RegistrationReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * طلبات التسجيل الواردة من صفحة الهبوط (ملاك ودلالون) — يراجعها المدير العام
 * فيعتمدها فيُنشأ الحساب، أو يرفضها بسبب يُحفظ مع الطلب.
 */
class RegistrationRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', RegistrationRequest::PENDING);
        if (! array_key_exists($status, RegistrationRequest::STATUS_LABELS) && $status !== 'all') {
            $status = RegistrationRequest::PENDING;
        }

        $query = RegistrationRequest::with(['role', 'reviewer', 'user'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->filled('role'), fn ($q) => $q->whereHas('role', fn ($r) => $r->where('key', $request->query('role'))))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.trim((string) $request->query('search')).'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('business_name', 'like', $term));
            })
            // المعلّقة الأقدم أولًا — من انتظر أطول يُراجع أولًا؛ والمراجَعة الأحدث أولًا.
            ->when($status === RegistrationRequest::PENDING, fn ($q) => $q->oldest(), fn ($q) => $q->latest());

        $counts = RegistrationRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('panel.registrations.index', [
            'requests' => $query->paginate(25)->withQueryString(),
            'status' => $status,
            'roles' => Role::whereIn('key', RegistrationRequest::ROLES)->orderBy('display_order')->get(),
            'counts' => [
                RegistrationRequest::PENDING => (int) ($counts[RegistrationRequest::PENDING] ?? 0),
                RegistrationRequest::APPROVED => (int) ($counts[RegistrationRequest::APPROVED] ?? 0),
                RegistrationRequest::REJECTED => (int) ($counts[RegistrationRequest::REJECTED] ?? 0),
                'all' => (int) $counts->sum(),
            ],
        ]);
    }

    public function approve(Request $request, RegistrationRequest $registration, RegistrationReview $review): RedirectResponse
    {
        $user = $review->approve($registration, $request->user());

        return back()->with('status', "تم اعتماد الطلب وإنشاء حساب «{$user->name}» — يدخل الآن بجواله وكلمة المرور التي اختارها.");
    }

    public function reject(Request $request, RegistrationRequest $registration, RegistrationReview $review): RedirectResponse
    {
        $data = $request->validate(['rejection_reason' => ['nullable', 'string', 'max:1000']]);

        $review->reject($registration, $request->user(), $data['rejection_reason'] ?? null);

        return back()->with('status', 'تم رفض الطلب.');
    }
}
