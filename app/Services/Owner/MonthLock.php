<?php

namespace App\Services\Owner;

use App\Models\MonthClosing;
use App\Models\MonthClosingBoat;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Validation\ValidationException;

/**
 * قفل الأشهر المُغلقة (O4): ما دام للشهر إغلاق فلا يُضاف فيه مصروف ولا
 * يُعدَّل ولا يُحذف، ومسيراته مجمَّدة الأرقام. السداد وحده يبقى مفتوحًا — لا
 * يغيّر ربح الشهر. والمبيعات تُؤرَّخ لحظة البيع ولا يُغلق إلا شهر انتهى، فلا
 * يدخل شهرًا مُغلقًا بيع.
 *
 * ومنه يأتي مؤجَّل الإهلاك الداخل إلى الشهر: ما أجّله إغلاق الشهر السابق
 * للقارب نفسه (الإغلاق بالتسلسل فلا ثغرة بين شهرين).
 */
class MonthLock
{
    public function isClosed(int $ownerId, int $year, int $month): bool
    {
        return MonthClosing::where('owner_id', $ownerId)->where('year', $year)->where('month', $month)->exists();
    }

    public function isClosedOn(int $ownerId, DateTimeInterface|string|null $date): bool
    {
        if ($date === null || $date === '') {
            return false;
        }

        $day = CarbonImmutable::parse($date);

        return $this->isClosed($ownerId, $day->year, $day->month);
    }

    /**
     * @throws ValidationException إن كان الشهر مُغلقًا
     */
    public function ensureOpen(int $ownerId, DateTimeInterface|string|null $date, string $field = 'date'): void
    {
        if (! $this->isClosedOn($ownerId, $date)) {
            return;
        }

        $day = CarbonImmutable::parse($date);

        throw ValidationException::withMessages([
            $field => 'شهر '.MonthClosing::label($day->year, $day->month).' مُغلق — أعد فتحه من «إغلاق الشهر» قبل أي تعديل فيه.',
        ]);
    }

    /**
     * مفاتيح الأشهر المُغلقة (YYYY-MM) — لتعليم صفوف القوائم.
     *
     * @return array<string, true>
     */
    public function closedKeys(int $ownerId): array
    {
        return MonthClosing::where('owner_id', $ownerId)->get(['year', 'month'])
            ->mapWithKeys(fn (MonthClosing $closing) => [sprintf('%04d-%02d', $closing->year, $closing->month) => true])
            ->all();
    }

    public function latest(int $ownerId): ?MonthClosing
    {
        return MonthClosing::where('owner_id', $ownerId)->latestFirst()->first();
    }

    /**
     * مؤجَّل إهلاك القارب الداخل إلى الشهر = ما أجّله إغلاق الشهر السابق له.
     */
    public function broughtForward(int $ownerId, int $boatId, int $year, int $month): float
    {
        $previous = CarbonImmutable::create($year, $month, 1)->subMonth();

        return round((float) MonthClosingBoat::where('boat_id', $boatId)
            ->whereHas('closing', fn ($q) => $q->where('owner_id', $ownerId)->where('year', $previous->year)->where('month', $previous->month))
            ->value('depreciation_deferred'), 2);
    }
}
