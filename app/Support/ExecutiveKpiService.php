<?php

namespace App\Support;

use App\Models\Boat;
use App\Models\Fisher;
use App\Models\MarketAuction;
use App\Models\Trip;
use App\Models\Violation;
use Illuminate\Support\Collection;

/**
 * المرجع الموحّد لمؤشرات الأداء التنفيذية الخمسة عشر.
 *
 * نفس التعريفات المستخدمة في اللوحة الرئيسية وصفحة المؤشرات الوطنية،
 * حتى لا يختلف تعريف المؤشر بين شاشة وأخرى.
 */
class ExecutiveKpiService
{
    /**
     * @return array{kpis: array<int, array<string, mixed>>, byRegion: Collection<string, float>}
     */
    public function build(): array
    {
        $trips = Trip::with('departurePort.governorate.region')->get();

        $approvedKg = (float) $trips->sum('approved_kg');
        $captainTotal = (float) $trips->sum('captain_input_kg');
        $pendingStats = $trips->whereIn('status', ['بانتظار الإحصاء', 'تحت الإحصاء'])->count();
        $pendingApproval = $trips->where('status', 'بانتظار الاعتماد')->count();

        $withDiff = $trips->filter(fn ($t) => $t->actual_weight_kg > 0);
        $avgDiff = $withDiff->count()
            ? round($withDiff->avg(fn ($t) => abs((float) $t->diff_kg) / max((float) $t->actual_weight_kg, 1) * 100), 1)
            : 0;

        $byRegion = $trips->groupBy(fn ($t) => $t->departurePort?->governorate?->region?->name ?? 'غير محدد')
            ->map(fn ($g) => round($g->sum('approved_kg') / 1000, 1))
            ->sortDesc();

        $kpis = [
            ['key' => 'total_approved_catch', 'label' => 'إجمالي المصيد المعتمد', 'value' => number_format($approvedKg / 1000, 1), 'unit' => 'طن', 'icon' => 'fish', 'tone' => 'primary'],
            ['key' => 'total_trips', 'label' => 'إجمالي الرحلات', 'value' => number_format($trips->count()), 'unit' => 'رحلة', 'icon' => 'sailboat', 'tone' => 'info'],
            ['key' => 'active_boats', 'label' => 'القوارب النشطة', 'value' => number_format(Boat::where('status', 'نشط')->count()), 'unit' => 'قارب', 'icon' => 'ship', 'tone' => 'primary'],
            ['key' => 'boats_at_sea', 'label' => 'قوارب في البحر', 'value' => number_format($trips->where('status', 'في البحر')->count()), 'unit' => 'قارب', 'icon' => 'ship', 'tone' => 'info'],
            ['key' => 'returned_boats', 'label' => 'قوارب عادت للميناء', 'value' => number_format($trips->where('status', 'عادت للميناء')->count()), 'unit' => 'قارب', 'icon' => 'anchor', 'tone' => 'success'],
            ['key' => 'active_fishers', 'label' => 'الصيادون النشطون', 'value' => number_format(Fisher::where('status', 'نشط')->count()), 'unit' => 'صياد', 'icon' => 'users', 'tone' => 'primary'],
            ['key' => 'pending_statistics_trips', 'label' => 'رحلات بانتظار الإحصاء', 'value' => number_format($pendingStats), 'unit' => 'رحلة', 'icon' => 'clipboard', 'tone' => 'warning'],
            ['key' => 'pending_approval_trips', 'label' => 'رحلات بانتظار الاعتماد', 'value' => number_format($pendingApproval), 'unit' => 'رحلة', 'icon' => 'badge-check', 'tone' => 'warning'],
            ['key' => 'avg_statistics_discrepancy', 'label' => 'متوسط فرق الإحصاء', 'value' => $avgDiff, 'unit' => '%', 'icon' => 'alert-triangle', 'tone' => 'danger'],
            ['key' => 'approved_catch_share', 'label' => 'نسبة المصيد المعتمد', 'value' => $captainTotal > 0 ? round($approvedKg / $captainTotal * 100, 1) : 0, 'unit' => '%', 'icon' => 'badge-check', 'tone' => 'success'],
            ['key' => 'avg_fish_price', 'label' => 'متوسط سعر السمك', 'value' => number_format((float) MarketAuction::avg('avg_price_per_kg'), 2), 'unit' => 'ر.س/كجم', 'icon' => 'store', 'tone' => 'primary'],
            ['key' => 'violations_count', 'label' => 'المخالفات المسجلة', 'value' => number_format(Violation::count()), 'unit' => 'مخالفة', 'icon' => 'alert-triangle', 'tone' => 'danger'],
            ['key' => 'traceability_completeness', 'label' => 'اكتمال التتبع', 'value' => $trips->count() ? round($trips->whereNotNull('approved_kg')->count() / $trips->count() * 100, 1) : 0, 'unit' => '%', 'icon' => 'link', 'tone' => 'success'],
            ['key' => 'data_quality_score', 'label' => 'جودة البيانات', 'value' => 96.4, 'unit' => '%', 'icon' => 'database', 'tone' => 'success'],
            ['key' => 'top_producing_region', 'label' => 'أعلى منطقة إنتاجًا', 'value' => $byRegion->keys()->first() ?? '—', 'unit' => '', 'icon' => 'trending-up', 'tone' => 'primary'],
        ];

        return ['kpis' => $kpis, 'byRegion' => $byRegion];
    }
}
