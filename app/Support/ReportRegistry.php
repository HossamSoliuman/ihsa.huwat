<?php

namespace App\Support;

use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\Fisher;
use App\Models\FishingSite;
use App\Models\MarketAuction;
use App\Models\Port;
use App\Models\Region;
use App\Models\Species;
use App\Models\Trip;
use App\Models\Violation;
use Illuminate\Support\Collection;

/**
 * سجل التقارير الستة عشر وتعريف أعمدة كل تقرير.
 *
 * التقرير هنا ليس تفريغًا لجدول: لكل تقرير أعمدته المسمّاة بالعربية ومصدره
 * الصريح، حتى يخرج ملف CSV وصفحة الطباعة بالشكل نفسه ولا يتسرّب اسم عمود
 * تقني إلى مستند رسمي.
 */
class ReportRegistry
{
    public const CATEGORIES = [
        'production' => ['title' => 'الإنتاج والتقارير الوطنية', 'icon' => 'trending-up', 'tone' => 'primary'],
        'statistics' => ['title' => 'الإحصاء وجودة البيانات', 'icon' => 'clipboard', 'tone' => 'info'],
        'fleet' => ['title' => 'الأسطول والصيادون', 'icon' => 'ship', 'tone' => 'info'],
        'resources' => ['title' => 'الموارد والاستدامة', 'icon' => 'leaf', 'tone' => 'success'],
        'geography' => ['title' => 'الموانئ ومواقع الصيد', 'icon' => 'map-pin', 'tone' => 'warning'],
        'compliance' => ['title' => 'الامتثال والمخالفات', 'icon' => 'shield-alert', 'tone' => 'danger'],
        'markets' => ['title' => 'الأسواق والأسعار', 'icon' => 'store', 'tone' => 'primary'],
    ];

    /**
     * تعريف كل تقرير: عنوانه ووصفه وفئته وأيقونته.
     *
     * @return array<string, array{title: string, desc: string, category: string, icon: string}>
     */
    public static function definitions(): array
    {
        return [
            'daily' => ['title' => 'تقرير الإنتاج اليومي', 'desc' => 'سجلات المصيد مجمّعة حسب اليوم', 'category' => 'production', 'icon' => 'calendar'],
            'monthly' => ['title' => 'تقرير الإنتاج الشهري', 'desc' => 'ملخص الإنتاج والأنواع لكل شهر', 'category' => 'production', 'icon' => 'trending-up'],
            'annual' => ['title' => 'تقرير الإنتاج السنوي', 'desc' => 'الإنتاج السنوي حسب السنة والنوع', 'category' => 'production', 'icon' => 'gauge'],
            'national' => ['title' => 'التقرير الوطني للمصايد', 'desc' => 'الملخص الوطني الشامل حسب المنطقة', 'category' => 'production', 'icon' => 'crown'],
            'bulletin' => ['title' => 'النشرة السنوية للمصايد', 'desc' => 'إصدار سنوي رسمي متعدد الصفحات قابل للطباعة', 'category' => 'production', 'icon' => 'book-open'],
            'statistics' => ['title' => 'تقرير الإحصاء الميداني', 'desc' => 'عمليات الإحصاء الميداني وتقدّمها', 'category' => 'statistics', 'icon' => 'clipboard'],
            'diffs' => ['title' => 'تقرير الفروقات', 'desc' => 'الفروقات بين إدخال الكابتن والوزن الفعلي', 'category' => 'statistics', 'icon' => 'scale'],
            'trips' => ['title' => 'تقرير الرحلات', 'desc' => 'كل الرحلات وحالاتها وكمياتها', 'category' => 'fleet', 'icon' => 'sailboat'],
            'boats' => ['title' => 'تقرير القوارب', 'desc' => 'القوارب ورخصها وأداؤها', 'category' => 'fleet', 'icon' => 'ship'],
            'fishers' => ['title' => 'تقرير الصيادين', 'desc' => 'الصيادون والكباتنة المرخّصون', 'category' => 'fleet', 'icon' => 'users'],
            'species' => ['title' => 'تقرير الأنواع', 'desc' => 'الأنواع السمكية وحالة مخزونها', 'category' => 'resources', 'icon' => 'fish'],
            'sustainability' => ['title' => 'المخزون والاستدامة', 'desc' => 'حالة المخزون وضغط الاستغلال', 'category' => 'resources', 'icon' => 'leaf'],
            'ports' => ['title' => 'تقرير الموانئ', 'desc' => 'نشاط الموانئ وإحصاءاتها', 'category' => 'geography', 'icon' => 'anchor'],
            'sites' => ['title' => 'تقرير مواقع الصيد', 'desc' => 'مواقع الصيد وضغط الصيد عليها', 'category' => 'geography', 'icon' => 'map-pin'],
            'violations' => ['title' => 'تقرير المخالفات', 'desc' => 'المخالفات والإجراءات المتخذة', 'category' => 'compliance', 'icon' => 'shield-alert'],
            'prices' => ['title' => 'الأسعار والأسواق', 'desc' => 'الأسعار والمبيعات في الأسواق والمزادات', 'category' => 'markets', 'icon' => 'store'],
        ];
    }

    public static function exists(string $id): bool
    {
        return array_key_exists($id, self::definitions());
    }

    public static function definition(string $id): array
    {
        return self::definitions()[$id];
    }

    /**
     * صفوف التقرير جاهزة للعرض والتصدير: مفاتيح عربية وقيم مسطّحة.
     */
    public static function rows(string $id): Collection
    {
        return match ($id) {
            'daily' => self::catchGroupedBy(fn ($r) => $r->recorded_at->toDateString(), 'اليوم'),
            'monthly' => self::catchGroupedBy(fn ($r) => $r->recorded_at->format('Y-m'), 'الشهر'),
            'annual' => self::catchGroupedBy(fn ($r) => $r->recorded_at->format('Y'), 'السنة'),
            'national' => self::national(),
            'bulletin' => self::catchGroupedBy(fn ($r) => $r->recorded_at->format('Y'), 'السنة'),
            'statistics' => self::fieldStatistics(),
            'diffs' => self::discrepancies(),
            'trips' => self::trips(),
            'boats' => self::boats(),
            'fishers' => self::fishers(),
            'species' => self::species(),
            'sustainability' => self::sustainability(),
            'ports' => self::ports(),
            'sites' => self::sites(),
            'violations' => self::violations(),
            'prices' => self::prices(),
        };
    }

    /**
     * عدد سجلات كل تقرير — يُعرض على البطاقة قبل التصدير.
     *
     * @return array<string, int>
     */
    public static function counts(): array
    {
        $catchDays = CatchRecord::get(['recorded_at']);

        return [
            'daily' => $catchDays->pluck('recorded_at')->map->toDateString()->unique()->count(),
            'monthly' => $catchDays->pluck('recorded_at')->map->format('Y-m')->unique()->count(),
            'annual' => $catchDays->pluck('recorded_at')->map->format('Y')->unique()->count(),
            'national' => Region::count(),
            'bulletin' => $catchDays->pluck('recorded_at')->map->format('Y')->unique()->count(),
            'statistics' => Trip::count(),
            'diffs' => Trip::whereNotNull('diff_kg')->count(),
            'trips' => Trip::count(),
            'boats' => Boat::count(),
            'fishers' => Fisher::count(),
            'species' => Species::count(),
            'sustainability' => Species::count(),
            'ports' => Port::count(),
            'sites' => FishingSite::count(),
            'violations' => Violation::count(),
            'prices' => MarketAuction::count(),
        ];
    }

    private static function catchGroupedBy(callable $key, string $label): Collection
    {
        return CatchRecord::with('species')->get()
            ->groupBy($key)
            ->map(fn (Collection $group, string $bucket) => [
                $label => $bucket,
                'المصيد (كجم)' => round((float) $group->sum('quantity_kg'), 1),
                'عدد السجلات' => $group->count(),
                'عدد الرحلات' => $group->pluck('trip_id')->unique()->count(),
                'عدد الأنواع' => $group->pluck('species_id')->unique()->count(),
                'القيمة (ريال)' => round($group->sum(fn ($r) => (float) $r->quantity_kg * (float) ($r->price_per_kg ?? 0)), 2),
            ])
            ->sortKeysDesc()
            ->values();
    }

    private static function national(): Collection
    {
        return Region::orderByDesc('total_catch_tons')->get()->map(fn (Region $region) => [
            'المنطقة' => $region->name,
            'الرمز' => $region->code ?? '—',
            'طول الساحل (كم)' => (float) $region->coast_length_km,
            'المحافظات' => $region->governorates_count,
            'الموانئ' => $region->ports_count,
            'المصيد (طن)' => (float) $region->total_catch_tons,
            'القوارب النشطة' => $region->active_boats,
            'الصيادون النشطون' => $region->active_fishers,
        ]);
    }

    private static function fieldStatistics(): Collection
    {
        return Trip::with('departurePort')->orderByDesc('departure_time')->get()->map(fn (Trip $trip) => [
            'رقم الرحلة' => $trip->trip_number,
            'الميناء' => $trip->departurePort?->name ?? '—',
            'الكابتن' => $trip->captain_name ?? '—',
            'إدخال الكابتن (كجم)' => $trip->captain_input_kg,
            'الوزن الفعلي (كجم)' => $trip->actual_weight_kg,
            'المعتمد (كجم)' => $trip->approved_kg,
            'موظف الإحصاء' => $trip->statistics_officer ?? '—',
            'الحالة' => $trip->status,
        ]);
    }

    private static function discrepancies(): Collection
    {
        return Trip::with('departurePort')->whereNotNull('diff_kg')->orderByDesc('diff_kg')->get()->map(fn (Trip $trip) => [
            'رقم الرحلة' => $trip->trip_number,
            'الميناء' => $trip->departurePort?->name ?? '—',
            'إدخال الكابتن (كجم)' => $trip->captain_input_kg,
            'الوزن الفعلي (كجم)' => $trip->actual_weight_kg,
            'الفرق (كجم)' => $trip->diff_kg,
            'نسبة الفرق %' => $trip->actual_weight_kg > 0
                ? round(abs((float) $trip->diff_kg) / (float) $trip->actual_weight_kg * 100, 1)
                : 0,
            'الحالة' => $trip->status,
        ]);
    }

    private static function trips(): Collection
    {
        return Trip::with(['boat', 'departurePort'])->orderByDesc('departure_time')->get()->map(fn (Trip $trip) => [
            'رقم الرحلة' => $trip->trip_number,
            'القارب' => $trip->boat?->name ?? '—',
            'ميناء المغادرة' => $trip->departurePort?->name ?? '—',
            'المغادرة' => $trip->departure_time?->format('Y-m-d H:i') ?? '—',
            'العودة' => $trip->return_time?->format('Y-m-d H:i') ?? '—',
            'المدة (ساعة)' => $trip->duration_hours,
            'أداة الصيد' => $trip->gear_type ?? '—',
            'المعتمد (كجم)' => $trip->approved_kg,
            'الحالة' => $trip->status,
        ]);
    }

    private static function boats(): Collection
    {
        return Boat::with('port')->orderByDesc('total_catch_kg')->get()->map(fn (Boat $boat) => [
            'اسم القارب' => $boat->name,
            'رقم القارب' => $boat->boat_number,
            'الميناء' => $boat->port?->name ?? '—',
            'النوع' => $boat->boat_type ?? '—',
            'الطول (م)' => $boat->length_m,
            'المالك' => $boat->owner ?? '—',
            'الكابتن' => $boat->captain ?? '—',
            'إجمالي المصيد (كجم)' => (float) $boat->total_catch_kg,
            'حالة الرخصة' => $boat->license_status,
            'المخالفات' => $boat->violations_count,
            'الحالة' => $boat->status,
        ]);
    }

    private static function fishers(): Collection
    {
        return Fisher::with('port')->orderBy('name')->get()->map(fn (Fisher $fisher) => [
            'الاسم' => $fisher->name,
            'رقم الهوية' => $fisher->national_id,
            'الميناء' => $fisher->port?->name ?? '—',
            'الصفة' => $fisher->role,
            'رقم الرخصة' => $fisher->license_number ?? '—',
            'حالة الرخصة' => $fisher->license_status,
            'الحالة' => $fisher->status,
        ]);
    }

    private static function species(): Collection
    {
        return Species::orderByDesc('catch_kg')->get()->map(fn (Species $species) => [
            'الاسم العربي' => $species->name_ar,
            'الاسم العلمي' => $species->name_sci ?? '—',
            'الاسم الإنجليزي' => $species->name_en ?? '—',
            'التصنيف' => $species->category,
            'المصيد (كجم)' => (float) $species->catch_kg,
            'الرحلات' => $species->trips_count,
            'القوارب' => $species->boats_count,
            'أعلى ميناء' => $species->top_port ?? '—',
            'حالة المخزون' => $species->status,
        ]);
    }

    private static function sustainability(): Collection
    {
        return Species::orderByDesc('catch_kg')->get()->map(fn (Species $species) => [
            'النوع' => $species->name_ar,
            'حالة المخزون' => $species->status,
            'المصيد (كجم)' => (float) $species->catch_kg,
            'الرحلات' => $species->trips_count,
            'متوسط المصيد لكل رحلة (كجم)' => $species->trips_count > 0
                ? round((float) $species->catch_kg / $species->trips_count, 1)
                : 0,
            'الموسم' => $species->season ?? '—',
        ]);
    }

    private static function ports(): Collection
    {
        return Port::with('governorate.region')->orderByDesc('total_catch_tons')->get()->map(fn (Port $port) => [
            'الميناء' => $port->name,
            'المحافظة' => $port->governorate?->name ?? '—',
            'المنطقة' => $port->governorate?->region?->name ?? '—',
            'القوارب' => $port->boats_count,
            'القوارب النشطة' => $port->active_boats,
            'الصيادون' => $port->fishers_count,
            'الرحلات الشهرية' => $port->monthly_trips,
            'المصيد (طن)' => (float) $port->total_catch_tons,
            'موظفو الإحصاء' => $port->statistics_staff,
            'الحالة' => $port->status,
        ]);
    }

    private static function sites(): Collection
    {
        return FishingSite::with('port')->orderByDesc('catch_kg')->get()->map(fn (FishingSite $site) => [
            'الموقع' => $site->name,
            'الميناء' => $site->port?->name ?? '—',
            'النوع' => $site->site_type ?? '—',
            'مستوى الضغط' => $site->pressure_level,
            'المصيد (كجم)' => (float) $site->catch_kg,
            'الرحلات' => $site->trips_count,
            'القوارب' => $site->boats_count,
            'الحالة' => $site->status,
        ]);
    }

    private static function violations(): Collection
    {
        return Violation::with('boat')->orderByDesc('date')->get()->map(fn (Violation $violation) => [
            'نوع المخالفة' => $violation->violation_type,
            'القارب' => $violation->boat?->name ?? '—',
            'الخطورة' => $violation->severity,
            'الموقع' => $violation->location ?? '—',
            'الغرامة (ريال)' => $violation->fine_amount,
            'الإجراء' => $violation->action ?? '—',
            'التاريخ' => $violation->date?->toDateString() ?? '—',
            'الحالة' => $violation->status,
        ]);
    }

    private static function prices(): Collection
    {
        return MarketAuction::with(['market', 'species'])->orderByDesc('auction_date')->get()->map(fn (MarketAuction $auction) => [
            'السوق' => $auction->market?->name ?? '—',
            'النوع' => $auction->species?->name_ar ?? '—',
            'تاريخ المزاد' => $auction->auction_date?->toDateString() ?? '—',
            'المعروض (كجم)' => (float) $auction->quantity_offered_kg,
            'المباع (كجم)' => (float) $auction->quantity_sold_kg,
            'متوسط السعر (ريال/كجم)' => (float) $auction->avg_price_per_kg,
            'نوع المشتري' => $auction->buyer_type ?? '—',
        ]);
    }
}
