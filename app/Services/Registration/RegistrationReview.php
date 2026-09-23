<?php

namespace App\Services\Registration;

use App\Models\AuditLog;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * مراجعة طلبات التسجيل: الاعتماد ينشئ الحساب بدور الطلب وكلمة مروره المجزّأة
 * كما هي، والرفض يحفظ سببه. الطلب يُراجع مرّة واحدة فقط.
 */
class RegistrationReview
{
    public function approve(RegistrationRequest $request, User $admin): User
    {
        $this->ensurePending($request);

        // قد يُنشأ حساب بالجوال نفسه بعد تقديم الطلب (من صفحة الحسابات مثلًا).
        if (User::where('phone', $request->phone)->exists()) {
            throw ValidationException::withMessages(['review' => 'يوجد حساب بهذا الجوال بالفعل — ارفض الطلب أو عدّل الحساب القائم.']);
        }

        // البريد اختياري: إن سبقه إليه حساب آخر يُنشأ الحساب بلا بريد بدل أن يتعطّل الاعتماد.
        $email = $request->email && ! User::where('email', $request->email)->exists() ? $request->email : null;

        return DB::transaction(function () use ($request, $admin, $email) {
            $user = new User([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $email,
                'role_id' => $request->role_id,
                'active' => true,
            ]);
            // المجزّأة تُنقل كما هي: الـ cast لا يعيد تجزئة قيمة مجزّأة.
            $user->password = $request->getRawOriginal('password');
            $user->save();

            $request->update([
                'status' => RegistrationRequest::APPROVED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'user_id' => $user->id,
            ]);

            $this->log($admin, 'اعتماد', $request);

            return $user;
        });
    }

    public function reject(RegistrationRequest $request, User $admin, ?string $reason): void
    {
        $this->ensurePending($request);

        $request->update([
            'status' => RegistrationRequest::REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $this->log($admin, 'رفض', $request);
    }

    private function ensurePending(RegistrationRequest $request): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages(['review' => 'هذا الطلب رُوجع من قبل.']);
        }
    }

    private function log(User $admin, string $action, RegistrationRequest $request): void
    {
        AuditLog::create([
            'user_email' => $admin->email ?? $admin->phone,
            'role' => $admin->app_role_key ?? 'admin',
            'action' => $action,
            'entity' => 'RegistrationRequest',
            'record_label' => $request->name.' ('.$request->phone.')',
            'details' => "{$action} طلب تسجيل بدور «{$request->role?->name}»",
            'ip' => request()->ip(),
        ]);
    }
}
