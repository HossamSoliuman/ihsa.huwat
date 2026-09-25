<?php

namespace App\Services\Dalal;

use App\Models\AuditLog;
use App\Models\DalalPartnership;
use App\Models\Role;
use App\Models\User;
use App\Services\Notifications\Notifier;
use Illuminate\Validation\ValidationException;

/**
 * طلبات المالكين: المالك يقترح على الدلال عمولةً وأجورًا ورسالة، والدلال
 * يقبل أو يرفض. المقبول وحده يُحسب على بيع مصيد المالك؛ بلا اتفاق مقبول
 * يبيع الدلال بلا اقتطاع (صافي المالك = كامل السطر).
 */
class PartnershipService
{
    public function __construct(private readonly Notifier $notifier) {}

    /**
     * المالك يرسل طلبًا أو يعدّل طلبه المعلّق أو يعيده بعد الرفض.
     *
     * @param  array{commission_pct:float|string, wage_pct?:float|string|null, message?:string|null}  $data
     */
    public function request(User $owner, User $dalal, array $data): DalalPartnership
    {
        if (! $dalal->active || ! $dalal->hasAppRole(Role::DALAL)) {
            throw ValidationException::withMessages(['dalal_id' => 'اختر دلالًا مفعّلًا.']);
        }

        $partnership = DalalPartnership::firstOrNew(['owner_id' => $owner->id, 'dalal_id' => $dalal->id]);

        if ($partnership->status === DalalPartnership::ACCEPTED) {
            throw ValidationException::withMessages(['partnership' => 'التعامل مع هذا الدلال مقبول بالفعل.']);
        }

        $partnership->fill([
            'commission_pct' => round((float) $data['commission_pct'], 2),
            'wage_pct' => round((float) ($data['wage_pct'] ?? 0), 2),
            'message' => $data['message'] ?? null,
            'status' => DalalPartnership::PENDING,
            'response_note' => null,
            'responded_at' => null,
        ])->save();

        $partnership->setRelation('owner', $owner)->setRelation('dalal', $dalal);
        $this->notifier->partnershipRequested($partnership);
        $this->log($owner, 'طلب تعامل', $partnership, "طلب التعامل مع {$dalal->name} بعمولة {$partnership->commission_pct}% وأجور {$partnership->wage_pct}%");

        return $partnership;
    }

    public function accept(User $dalal, DalalPartnership $partnership): DalalPartnership
    {
        return $this->answer($dalal, $partnership, DalalPartnership::ACCEPTED, null);
    }

    public function reject(User $dalal, DalalPartnership $partnership, ?string $note): DalalPartnership
    {
        return $this->answer($dalal, $partnership, DalalPartnership::REJECTED, $note);
    }

    /**
     * نِسب الاقتطاع على بيع مصيد المالك عند هذا الدلال.
     *
     * @return array{commission_pct:float, wage_pct:float}
     */
    public function termsFor(?int $ownerId, User $dalal): array
    {
        $accepted = $ownerId === null ? null : DalalPartnership::forDalal($dalal)->accepted()->where('owner_id', $ownerId)->first();

        return [
            'commission_pct' => (float) ($accepted?->commission_pct ?? 0),
            'wage_pct' => (float) ($accepted?->wage_pct ?? 0),
        ];
    }

    private function answer(User $dalal, DalalPartnership $partnership, string $status, ?string $note): DalalPartnership
    {
        if (! $partnership->isPending()) {
            throw ValidationException::withMessages(['partnership' => 'هذا الطلب رُدّ عليه من قبل.']);
        }

        $partnership->update(['status' => $status, 'response_note' => $note, 'responded_at' => now()]);
        $partnership->load('owner', 'dalal');

        $this->notifier->partnershipAnswered($partnership);
        $this->log($dalal, $status === DalalPartnership::ACCEPTED ? 'قبول تعامل' : 'رفض تعامل', $partnership, "{$partnership->status_label} طلب {$partnership->owner?->name}");

        return $partnership;
    }

    private function log(User $by, string $action, DalalPartnership $partnership, string $details): void
    {
        AuditLog::create([
            'user_email' => $by->email ?? $by->phone,
            'role' => $by->app_role_key ?? 'user',
            'action' => $action,
            'entity' => 'DalalPartnership',
            'record_label' => (string) $partnership->id,
            'details' => $details,
            'ip' => request()?->ip(),
        ]);
    }
}
