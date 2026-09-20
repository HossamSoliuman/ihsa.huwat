<?php

namespace App\Services\Owner;

use App\Models\AuditLog;
use App\Models\Boat;
use App\Models\Fisher;
use App\Models\FisherRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * كباتن المالك وطاقمه. الكابتن حساب دخول (users بدور captain يتبع المالك)
 * فوق سجلّ صياد في الوزارة (fishers) — فيدخل التطبيق ويظهر في صفحة الميناء
 * ومركز المعلومات معًا. الطاقم سجلّ صياد فقط.
 */
class StaffService
{
    public function createCaptain(User $owner, array $data): User
    {
        return DB::transaction(function () use ($owner, $data) {
            $captain = User::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'role_id' => Role::key(Role::CAPTAIN)->id,
                'owner_id' => $owner->id,
                'active' => $data['active'] ?? true,
            ]);

            $this->syncFisher($owner, $captain, $data);
            $this->assignBoat($owner, $captain, $data['boat_id'] ?? null);

            $this->log($owner, 'إنشاء كابتن', $captain->name);

            return $captain->load('fisher.boat', 'fisher.port');
        });
    }

    public function updateCaptain(User $captain, array $data): User
    {
        return DB::transaction(function () use ($captain, $data) {
            $attributes = [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'active' => $data['active'] ?? $captain->active,
            ];
            // كلمة المرور تُغيَّر فقط حين تُرسل؛ الفارغة تعني "كما هي".
            if (! empty($data['password'])) {
                $attributes['password'] = $data['password'];
            }
            $captain->update($attributes);

            if (! $captain->active) {
                $captain->tokens()->delete();
            }

            $this->syncFisher($captain->owner, $captain, $data);

            if (array_key_exists('boat_id', $data)) {
                $this->assignBoat($captain->owner, $captain, $data['boat_id']);
            }

            $this->log($captain->owner, 'تحديث كابتن', $captain->name);

            return $captain->load('fisher.boat', 'fisher.port');
        });
    }

    public function createCrew(User $owner, array $data): Fisher
    {
        $fisher = Fisher::create($this->crewAttributes($owner, $data));

        $this->log($owner, 'إضافة طاقم', $fisher->name);

        return $fisher->load('boat', 'port', 'fisherRole');
    }

    public function updateCrew(Fisher $fisher, array $data): Fisher
    {
        $fisher->update($this->crewAttributes($fisher->owner, $data));

        $this->log($fisher->owner, 'تحديث طاقم', $fisher->name);

        return $fisher->load('boat', 'port', 'fisherRole');
    }

    /**
     * سجلّ الصياد للكابتن: يُنشأ مع الحساب ويُحدَّث معه.
     */
    private function syncFisher(User $owner, User $captain, array $data): void
    {
        $boat = ! empty($data['boat_id']) ? Boat::forOwner($owner)->find($data['boat_id']) : null;

        Fisher::updateOrCreate(['user_id' => $captain->id], [
            'owner_id' => $owner->id,
            'name' => $captain->name,
            'phone' => $captain->phone,
            'email' => $captain->email,
            'national_id' => $data['national_id'],
            'id_type_id' => $data['id_type_id'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'fisher_role_id' => FisherRole::named(Fisher::CAPTAIN_ROLE)->id,
            'role' => Fisher::CAPTAIN_ROLE,
            'boat_id' => $boat?->id,
            'port_id' => $boat?->port_id ?? $data['port_id'] ?? $captain->fisher?->port_id,
            'license_number' => $data['license_number'] ?? null,
            'license_expiry' => $data['license_expiry'] ?? null,
            'experience_years' => $data['experience_years'] ?? 0,
            'status' => ($data['active'] ?? $captain->active) ? 'نشط' : 'غير نشط',
        ]);
    }

    /**
     * الكابتن على قارب واحد: يُسنَد إلى المختار ويُرفع عن غيره.
     */
    private function assignBoat(User $owner, User $captain, ?int $boatId): void
    {
        Boat::forOwner($owner)->where('captain_id', $captain->id)->whereKeyNot($boatId)->get()
            ->each(fn (Boat $boat) => $boat->update(['captain_id' => null]));

        if ($boatId) {
            Boat::forOwner($owner)->whereKey($boatId)->first()?->update(['captain_id' => $captain->id]);
        }
    }

    private function crewAttributes(User $owner, array $data): array
    {
        $boat = ! empty($data['boat_id']) ? Boat::forOwner($owner)->find($data['boat_id']) : null;

        return [
            'owner_id' => $owner->id,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'national_id' => $data['national_id'],
            'id_type_id' => $data['id_type_id'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'fisher_role_id' => $data['fisher_role_id'] ?? null,
            'boat_id' => $boat?->id,
            'port_id' => $boat?->port_id ?? $data['port_id'],
            'license_number' => $data['license_number'] ?? null,
            'license_expiry' => $data['license_expiry'] ?? null,
            'experience_years' => $data['experience_years'] ?? 0,
            'status' => $data['status'] ?? 'نشط',
        ];
    }

    private function log(User $owner, string $action, string $label): void
    {
        AuditLog::create([
            'user_email' => $owner->email ?? $owner->phone,
            'role' => 'owner',
            'action' => $action,
            'entity' => 'Fisher',
            'record_label' => $label,
            'details' => "{$action}: {$label}",
            'ip' => request()?->ip(),
        ]);
    }
}
