<?php

namespace App\Services\Counters;

use App\Models\AuditLog;
use App\Models\CounterApplication;
use App\Models\HiringRound;
use App\Models\Role;
use App\Models\StatisticsOfficer;
use App\Models\User;
use App\Services\Sms\SmsSender;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * مراجعة شركة التشغيل لطلبات التوظيف. الاعتماد ينشئ في معاملة واحدة حساب
 * العدّاد (بكلمة المرور التي اختارها) وسجلّ موظف الإحصاء في ميناء الجولة
 * باسم الشركة — فيصله طابور الميناء من أول دخول. والرفض يحفظ سببه.
 * القرار يصل المتقدّم رسالة نصية: لا حساب له قبل الاعتماد.
 */
class CounterApplicationReview
{
    public function __construct(private readonly SmsSender $sms) {}

    public function approve(CounterApplication $application, User $reviewer): User
    {
        $this->ensureReviewable($application);

        if (User::where('phone', $application->phone)->exists()) {
            throw ValidationException::withMessages(['review' => 'يوجد حساب بهذا الجوال بالفعل — ارفض الطلب أو راجع الحساب القائم.']);
        }

        $email = $application->email && ! User::where('email', $application->email)->exists() ? $application->email : null;

        $user = DB::transaction(function () use ($application, $reviewer, $email) {
            // القفل على الجولة يمنع اعتمادين متزامنين من تجاوز المقاعد.
            $round = HiringRound::whereKey($application->hiring_round_id)->lockForUpdate()->firstOrFail();

            if ($round->seatsLeft() === 0) {
                throw ValidationException::withMessages(['review' => 'اكتملت مقاعد هذه الجولة — زِد المقاعد أو ارفض الطلب.']);
            }

            $user = new User([
                'name' => $application->name,
                'phone' => $application->phone,
                'email' => $email,
                'role_id' => Role::key(Role::COUNTER)->id,
                'active' => true,
            ]);
            // المجزّأة تُنقل كما هي: الـ cast لا يعيد تجزئة قيمة مجزّأة.
            $user->password = $application->getRawOriginal('password');
            $user->save();

            StatisticsOfficer::create([
                'port_id' => $application->port_id,
                'user_id' => $user->id,
                'operating_company_id' => $application->operating_company_id,
                'counter_application_id' => $application->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'employee_number' => StatisticsOfficer::numberFor($user),
                'status' => StatisticsOfficer::ACTIVE,
            ]);

            $application->update([
                'status' => CounterApplication::APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'user_id' => $user->id,
            ]);

            $this->log($reviewer, 'اعتماد', $application);

            return $user;
        });

        $this->sms->send($application->phone,
            "قُبل طلبك عدّادًا في {$application->port?->name} لدى {$application->company?->name}. ادخل تطبيق حوات بجوالك وكلمة المرور التي اخترتها.");

        return $user;
    }

    public function reject(CounterApplication $application, User $reviewer, ?string $reason): void
    {
        $this->ensureReviewable($application);

        $application->update([
            'status' => CounterApplication::REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $this->log($reviewer, 'رفض', $application);

        $this->sms->send($application->phone,
            "نعتذر، لم يُقبل طلبك عدّادًا في {$application->port?->name}.".($reason ? " السبب: {$reason}" : ''));
    }

    private function ensureReviewable(CounterApplication $application): void
    {
        if (! $application->isPending()) {
            throw ValidationException::withMessages(['review' => 'هذا الطلب رُوجع من قبل أو سحبه صاحبه.']);
        }

        if (! $application->isVerified()) {
            throw ValidationException::withMessages(['review' => 'لم يوثّق المتقدّم جواله بعد.']);
        }
    }

    private function log(User $reviewer, string $action, CounterApplication $application): void
    {
        AuditLog::create([
            'user_email' => $reviewer->email ?? $reviewer->phone,
            'role' => $reviewer->app_role_key ?? 'admin',
            'action' => $action,
            'entity' => 'CounterApplication',
            'record_label' => $application->name.' ('.$application->phone.')',
            'details' => "{$action} طلب توظيف عدّاد في {$application->port?->name}",
            'ip' => request()->ip(),
        ]);
    }
}
