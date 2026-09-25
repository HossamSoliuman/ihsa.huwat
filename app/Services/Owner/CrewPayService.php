<?php

namespace App\Services\Owner;

use App\Models\AuditLog;
use App\Models\Boat;
use App\Models\CrewAdvance;
use App\Models\Fisher;
use App\Models\PayrollLine;
use App\Models\PayType;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * إعداد أجر الطاقم (النوع، الراتب، الأسهم، النسبة الخاصة، نسبة المالك من
 * القارب)، والسلف، وكشف حساب الفرد.
 */
class CrewPayService
{
    public function __construct(private readonly PayrollService $payrolls) {}

    public function saveSettings(Fisher $fisher, array $data, User $by): Fisher
    {
        $type = PayType::findOrFail($data['pay_type_id']);
        $custom = $type->isShare() && ($data['custom_share_percent'] ?? null) !== null && (float) $data['custom_share_percent'] > 0
            ? round((float) $data['custom_share_percent'], 2)
            : null;

        if ($custom !== null && $fisher->boat_id) {
            $others = (float) Fisher::forOwner($by)
                ->where('boat_id', $fisher->boat_id)
                ->whereKeyNot($fisher->id)
                ->where('pay_type_id', $type->id)
                ->sum('custom_share_percent');

            if ($others + $custom > 100) {
                throw ValidationException::withMessages(['custom_share_percent' => 'مجموع النسب الخاصة على القارب لا يتجاوز 100% (المستخدم لغيره '.rtrim(rtrim(number_format($others, 2), '0'), '.').'%).']);
            }
        }

        $fisher->update([
            'pay_type_id' => $type->id,
            'fixed_salary' => $type->isShare() ? null : round((float) $data['fixed_salary'], 2),
            'profit_shares' => $type->isShare() ? round((float) ($data['profit_shares'] ?? 1), 2) : 1,
            'custom_share_percent' => $custom,
        ]);

        $this->log($by, 'إعداد أجر', 'Fisher', $fisher->name, $type->isShare()
            ? ($custom !== null ? "نسبة خاصة {$custom}%" : "أسهم {$fisher->profit_shares}")
            : "راتب ثابت {$fisher->fixed_salary}");

        return $fisher;
    }

    public function setOwnerShare(Boat $boat, float $percent, User $by): Boat
    {
        $boat->update(['owner_share_percent' => round($percent, 2)]);

        $this->log($by, 'نسبة المالك', 'Boat', $boat->name, "نسبة المالك من صافي الربح {$boat->owner_share_percent}%");

        return $boat;
    }

    public function recordAdvance(User $owner, Fisher $fisher, array $data): CrewAdvance
    {
        return DB::transaction(function () use ($owner, $fisher, $data) {
            $advance = CrewAdvance::create([
                'owner_id' => $owner->id,
                'fisher_id' => $fisher->id,
                'member_name' => $fisher->name,
                'boat_id' => $fisher->boat_id,
                'date' => $data['date'],
                'amount' => round((float) $data['amount'], 2),
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $owner->id,
            ]);

            $this->payrolls->planAdvances($fisher->id);
            $this->log($owner, 'سلفة', 'CrewAdvance', $fisher->name, "سلفة {$advance->amount} بتاريخ {$advance->date->toDateString()}");

            return $advance;
        });
    }

    /**
     * السلفة المخصومة في مسير مسدَّد صُرفت وسُوّيت — لا تُحذف.
     */
    public function deleteAdvance(CrewAdvance $advance, User $by): void
    {
        if ($advance->fisher_id !== null) {
            $taken = (float) CrewAdvance::where('fisher_id', $advance->fisher_id)->whereKeyNot($advance->id)->sum('amount');
            $settled = (float) PayrollLine::where('fisher_id', $advance->fisher_id)->whereNotNull('paid_at')->sum('advances');

            if ($taken + 0.001 < $settled) {
                throw ValidationException::withMessages(['advance' => 'خُصمت هذه السلفة في مسير مسدَّد — لا تُحذف.']);
            }
        }

        DB::transaction(function () use ($advance, $by) {
            $advance->delete();

            if ($advance->fisher_id !== null) {
                $this->payrolls->planAdvances($advance->fisher_id);
            }

            $this->log($by, 'حذف سلفة', 'CrewAdvance', $advance->member_name, "حذف سلفة {$advance->amount}");
        });
    }

    /**
     * سلف كل فرد: المأخوذ، والمخصوم في مسيرات مسدَّدة، والمتبقي عليه.
     *
     * @param  Collection<int, int>  $fisherIds
     * @return array<int, array{taken: float, settled: float, outstanding: float}>
     */
    public function advanceBalances(Collection $fisherIds): array
    {
        $taken = CrewAdvance::whereIn('fisher_id', $fisherIds)->groupBy('fisher_id')->selectRaw('fisher_id, sum(amount) as total')->pluck('total', 'fisher_id');
        $settled = PayrollLine::whereIn('fisher_id', $fisherIds)->whereNotNull('paid_at')->groupBy('fisher_id')->selectRaw('fisher_id, sum(advances) as total')->pluck('total', 'fisher_id');

        return $fisherIds->mapWithKeys(fn (int $id) => [$id => [
            'taken' => round((float) ($taken[$id] ?? 0), 2),
            'settled' => round((float) ($settled[$id] ?? 0), 2),
            'outstanding' => round((float) ($taken[$id] ?? 0) - (float) ($settled[$id] ?? 0), 2),
        ]])->all();
    }

    /**
     * كشف حساب الفرد: سطور مسيراته من الأقدم، وسلفه، والمجاميع.
     *
     * @return array{lines: Collection<int, PayrollLine>, advances: Collection<int, CrewAdvance>, totals: array<string, float>}
     */
    public function statement(Fisher $fisher): array
    {
        $lines = $fisher->payrollLines()->with(['payroll', 'payType', 'paymentMethod'])->get()
            ->sortBy(fn (PayrollLine $line) => $line->payroll->period_key)->values();
        $advances = $fisher->advances()->with('paymentMethod')->orderBy('date')->get();
        $paid = $lines->filter(fn (PayrollLine $line) => $line->is_paid);
        $balance = $this->advanceBalances(collect([$fisher->id]))[$fisher->id];

        return [
            'lines' => $lines,
            'advances' => $advances,
            'totals' => [
                'earned' => round($lines->sum(fn (PayrollLine $line) => $line->gross), 2),
                'paid' => round($paid->sum('paid_amount'), 2),
                'advances_taken' => $balance['taken'],
                'advances_settled' => $balance['settled'],
                'advances_outstanding' => $balance['outstanding'],
                'unpaid' => round($lines->reject(fn (PayrollLine $line) => $line->is_paid)->sum('net'), 2),
            ],
        ];
    }

    private function log(User $by, string $action, string $entity, string $label, string $details): void
    {
        AuditLog::create([
            'user_email' => $by->email ?? $by->phone,
            'role' => $by->app_role_key ?? 'owner',
            'action' => $action,
            'entity' => $entity,
            'record_label' => $label,
            'details' => $details,
            'ip' => request()?->ip(),
        ]);
    }
}
