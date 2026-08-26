<?php

namespace App\Support;

use App\Models\Alert;
use App\Models\Boat;
use App\Models\BycatchRecord;
use App\Models\FishingSeason;
use App\Models\Trip;
use Illuminate\Support\Carbon;

/**
 * توليد إنذارات مركز الإنذارات من بيانات النظام نفسها.
 *
 * لا يخترع التوليد تنبيهًا: كل إنذار له قاعدة تقرؤها من سجل قائم — رخصة مضى
 * تاريخها، فرق بين إدخال الكابتن والوزن الفعلي، رحلة عادت ولم تُعتمد، موسم على
 * الأبواب، صيد عرضي لكائن محمي.
 *
 * العنوان هو مفتاح التفرقة، فإعادة التوليد تحدّث الإنذار القائم ولا تكرّره، ولا
 * تمسّ ما أُغلق أو أُسند إلى مسؤول.
 */
class AlertGenerator
{
    /** الفرق الذي يستحق تنبيهًا — نسبة من إدخال الكابتن. */
    private const DIFF_THRESHOLD = 0.15;

    /** كائنات بحرية محمية يستوجب صيدها العرضي تنبيهًا مهما كانت الكمية. */
    private const PROTECTED = ['سلحفاة', 'دلفين', 'قرش', 'أطوم', 'شعاب'];

    /**
     * @return array{generated: int, updated: int}
     */
    public static function run(): array
    {
        $generated = 0;
        $updated = 0;

        foreach (self::candidates() as $candidate) {
            $existing = Alert::where('title', $candidate['title'])->first();

            // إنذار أُغلق أو أُسند لمسؤول لا يُعاد فتحه من التوليد التلقائي.
            if ($existing !== null && ($existing->status === 'تم الحل' || $existing->assigned_to !== null)) {
                continue;
            }

            if ($existing === null) {
                Alert::create($candidate);
                $generated++;

                continue;
            }

            $existing->update($candidate);
            $updated++;
        }

        return ['generated' => $generated, 'updated' => $updated];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function candidates(): array
    {
        $today = Carbon::today();

        return array_merge(
            self::expiredLicenses($today),
            self::highDifferences(),
            self::staleTrips($today),
            self::upcomingSeasons($today),
            self::protectedBycatch(),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function expiredLicenses(Carbon $today): array
    {
        return Boat::with('port')
            ->where(fn ($query) => $query->whereDate('license_expiry', '<', $today)->orWhere('license_status', 'منتهية'))
            ->get()
            ->map(fn (Boat $boat) => [
                'title' => "رخصة منتهية — {$boat->name}",
                'type' => 'رخصة منتهية',
                'severity' => 'مرتفع',
                'port' => $boat->port?->name,
                'boat' => $boat->name,
                'description' => 'رخصة القارب انتهت'.($boat->license_expiry ? ' بتاريخ '.$boat->license_expiry->toDateString() : '').' ولم تُجدَّد بعد.',
                'date' => $today->toDateString(),
                'status' => 'جديدة',
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function highDifferences(): array
    {
        return Trip::with(['boat.port'])
            ->where('captain_input_kg', '>', 0)
            ->whereNotNull('diff_kg')
            ->get()
            ->filter(fn (Trip $trip) => abs((float) $trip->diff_kg) / (float) $trip->captain_input_kg > self::DIFF_THRESHOLD)
            ->map(function (Trip $trip) {
                $ratio = abs((float) $trip->diff_kg) / (float) $trip->captain_input_kg;

                return [
                    'title' => "فرق مرتفع في الرحلة {$trip->trip_number}",
                    'type' => 'فرق مرتفع',
                    'severity' => $ratio > 0.3 ? 'حرج' : 'مرتفع',
                    'port' => $trip->boat?->port?->name,
                    'boat' => $trip->boat?->name,
                    'description' => 'الفرق بين إدخال الكابتن والوزن الفعلي '.number_format(abs((float) $trip->diff_kg), 1).' كجم ('.round($ratio * 100).'%).',
                    'date' => optional($trip->return_time)->toDateString() ?? Carbon::today()->toDateString(),
                    'status' => 'جديدة',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function staleTrips(Carbon $today): array
    {
        return Trip::with(['boat.port'])
            ->whereNotIn('status', ['معتمدة', 'ملغاة'])
            ->whereNotNull('return_time')
            ->where('return_time', '<', $today->copy()->subDays(3))
            ->get()
            ->map(fn (Trip $trip) => [
                'title' => "رحلة غير معتمدة — {$trip->trip_number}",
                'type' => 'رحلة غير معتمدة',
                'severity' => 'متوسط',
                'port' => $trip->boat?->port?->name,
                'boat' => $trip->boat?->name,
                'description' => 'عادت الرحلة منذ '.(int) $trip->return_time->diffInDays($today).' يومًا وما زالت في حالة «'.$trip->status.'».',
                'date' => $trip->return_time->toDateString(),
                'status' => 'جديدة',
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function upcomingSeasons(Carbon $today): array
    {
        return FishingSeason::whereNotNull('start_date')
            ->whereBetween('start_date', [$today, $today->copy()->addDays(14)])
            ->get()
            ->map(fn (FishingSeason $season) => [
                'title' => "اقتراب موسم — {$season->name}",
                'type' => 'اقتراب موسم صيد',
                'severity' => 'منخفض',
                'region' => $season->region,
                'species' => $season->species,
                'description' => 'يبدأ الموسم في '.$season->start_date->toDateString().' — راجع الرخص الصادرة والحصة المخصصة.',
                'date' => $today->toDateString(),
                'status' => 'جديدة',
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function protectedBycatch(): array
    {
        return BycatchRecord::with('trip.boat.port')
            ->get()
            ->filter(fn (BycatchRecord $record) => collect(self::PROTECTED)->contains(
                fn (string $name) => str_contains((string) $record->species_name, $name),
            ))
            ->map(fn (BycatchRecord $record) => [
                'title' => "صيد عرضي لكائن محمي — {$record->species_name}",
                'type' => 'صيد عرضي لكائن حساس',
                'severity' => 'مرتفع',
                'port' => $record->trip?->boat?->port?->name,
                'boat' => $record->trip?->boat?->name,
                'species' => $record->species_name,
                'description' => 'سُجّل '.number_format((float) $record->quantity_kg, 1).' كجم من كائن محمي — الإجراء المتخذ: '.($record->action_taken ?: 'لم يُسجَّل').'.',
                'date' => Carbon::today()->toDateString(),
                'status' => 'جديدة',
            ])
            ->values()
            ->all();
    }
}
