<?php

namespace App\Services\Counters;

use App\Models\AuditLog;
use App\Models\CounterTransfer;
use App\Models\Port;
use App\Models\StatisticsOfficer;
use App\Models\Trip;
use App\Models\User;
use App\Services\Notifications\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * إدارة العدّاد بعد توظيفه: الإيقاف ورفعه والنقل بين الموانئ. تستدعيها بوابة
 * الشركة لعدّاديها، وصفحة العدّادين عند المدير العام لكل عدّاد.
 *
 * الإيقاف يعطّل الحساب (users.active) فيمنعه حارسا اللوحة والتطبيق ويُلغي
 * رموزه؛ وإيقاف المدير العام لا ترفعه الشركة. الطابور نفسه لا يتغيّر:
 * Trip::forCounter يُحسب على ميناء السجلّ، فالنقل يغيّر الميناء وكفى.
 */
class CounterManagement
{
    public function __construct(private readonly Notifier $notifier) {}

    public function suspend(StatisticsOfficer $officer, User $by, ?string $reason): void
    {
        if ($officer->isSuspended()) {
            throw ValidationException::withMessages(['counter' => 'العدّاد موقوف بالفعل.']);
        }

        DB::transaction(function () use ($officer, $by, $reason) {
            $officer->update([
                'status' => StatisticsOfficer::SUSPENDED,
                'suspended_at' => now(),
                'suspended_by' => $by->id,
                'suspension_reason' => $reason,
            ]);

            if ($officer->user) {
                $officer->user->update(['active' => false]);
                $officer->user->tokens()->delete();
            }
        });

        $this->log($by, 'إيقاف', $officer, $reason);
    }

    public function reactivate(StatisticsOfficer $officer, User $by): void
    {
        if (! $officer->isSuspended()) {
            throw ValidationException::withMessages(['counter' => 'العدّاد غير موقوف.']);
        }

        if ($officer->isSuspendedByMinistry() && ! $by->isSuperAdmin()) {
            throw ValidationException::withMessages(['counter' => 'أوقفته الوزارة — لا يرفع إيقافه إلا المدير العام.']);
        }

        DB::transaction(function () use ($officer) {
            $officer->update([
                'status' => StatisticsOfficer::ACTIVE,
                'suspended_at' => null,
                'suspended_by' => null,
                'suspension_reason' => null,
            ]);

            $officer->user?->update(['active' => true]);
        });

        $this->log($by, 'رفع الإيقاف', $officer);
    }

    /**
     * نقل العدّاد إلى ميناء آخر. لا يُنقل وفي يده رحلة استلمها ولم يُكمل عدّها
     * — تبقى معلّقة في ميناء لم يعد يراه.
     */
    public function transfer(StatisticsOfficer $officer, Port $to, User $by, ?string $reason): CounterTransfer
    {
        if ($officer->port_id === $to->id) {
            throw ValidationException::withMessages(['to_port_id' => 'العدّاد يعمل في هذا الميناء أصلًا.']);
        }

        if ($officer->user_id && Trip::where('counter_id', $officer->user_id)->where('status', Trip::COUNTING)->exists()) {
            throw ValidationException::withMessages(['to_port_id' => 'للعدّاد رحلة تحت العد — يُكملها أولًا ثم يُنقل.']);
        }

        $from = $officer->port;

        $transfer = DB::transaction(function () use ($officer, $to, $by, $reason) {
            $transfer = CounterTransfer::create([
                'statistics_officer_id' => $officer->id,
                'from_port_id' => $officer->port_id,
                'to_port_id' => $to->id,
                'moved_by' => $by->id,
                'reason' => $reason,
            ]);

            $officer->update(['port_id' => $to->id]);

            return $transfer;
        });

        $this->log($by, 'نقل', $officer, "من {$from?->name} إلى {$to->name}");
        $this->notifier->counterTransferred($officer->load('port', 'user'), $from);

        return $transfer;
    }

    private function log(User $by, string $action, StatisticsOfficer $officer, ?string $note = null): void
    {
        AuditLog::create([
            'user_email' => $by->email ?? $by->phone,
            'role' => $by->app_role_key ?? 'admin',
            'action' => $action,
            'entity' => 'StatisticsOfficer',
            'record_label' => $officer->name.' ('.$officer->employee_number.')',
            'details' => trim("{$action} عدّاد ".($note ? "— {$note}" : '')),
            'ip' => request()->ip(),
        ]);
    }
}
