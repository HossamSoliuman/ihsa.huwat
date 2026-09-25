<?php

namespace App\Services\Owner;

use App\Models\Boat;
use App\Models\Fisher;
use App\Models\FleetDocument;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * تنبيهات أسطول المالك في رئيسته: وثائق منتهية أو تنتهي خلال 30 يومًا،
 * وفحوصات ورخص قوارب حلّ موعدها أو يقترب. الأقرب موعدًا أولًا.
 */
class FleetAlerts
{
    /**
     * @return Collection<int, array{severity: string, title: string, subject: string, date: Carbon, days: int, url: string}>
     */
    public function for(User $owner): Collection
    {
        $horizon = now()->addDays(FleetDocument::EXPIRING_DAYS)->endOfDay();
        $alerts = collect();

        FleetDocument::forOwner($owner)->needsAttention()->with(['type', 'documentable'])->get()
            ->each(function (FleetDocument $doc) use ($alerts) {
                $alerts->push($this->alert(
                    $doc->type?->name ?? 'وثيقة',
                    $doc->documentable instanceof Fisher ? 'فرد الطاقم '.$doc->documentable->name : 'القارب '.($doc->documentable?->name ?? '—'),
                    $doc->expiry_date,
                    route('panel.owner.documents', ['status' => $doc->status]),
                ));
            });

        Boat::forOwner($owner)
            ->where(fn ($q) => $q->whereDate('next_inspection_date', '<=', $horizon)->orWhereDate('license_expiry', '<=', $horizon))
            ->get()
            ->each(function (Boat $boat) use ($alerts, $horizon) {
                if ($boat->next_inspection_date && $boat->next_inspection_date <= $horizon) {
                    $alerts->push($this->alert('موعد الفحص', 'القارب '.$boat->name, $boat->next_inspection_date, route('panel.owner.inspections', ['boat' => $boat->id])));
                }

                if ($boat->license_expiry && $boat->license_expiry <= $horizon) {
                    $alerts->push($this->alert('رخصة القارب', 'القارب '.$boat->name, $boat->license_expiry, route('panel.owner.boats')));
                }
            });

        return $alerts->sortBy('date')->values();
    }

    private function alert(string $title, string $subject, Carbon $date, string $url): array
    {
        $days = (int) now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false);

        return [
            'severity' => $days < 0 ? 'danger' : 'warn',
            'title' => $title,
            'subject' => $subject,
            'date' => $date,
            'days' => $days,
            'url' => $url,
        ];
    }
}
