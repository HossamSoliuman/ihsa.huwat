<?php

namespace App\Support;

use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\FishingSite;
use App\Models\Species;
use App\Models\Trip;
use App\Models\Violation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * محلّل حوات — يجيب على أسئلة القسم من بيانات النظام نفسها.
 *
 * النسخة الأصلية في Base44 تمرّر السؤال إلى نموذج لغوي. هنا الإجابة محسوبة
 * لا مولّدة: كل رقم في الرد يأتي من استعلام، ولا يُصاغ نص عن بيانات غير موجودة.
 * السؤال يُوجَّه إلى موضوع من الموضوعات المعروفة بمطابقة كلماته المفتاحية،
 * وما لا يُطابق أيًا منها يُردّ عليه بالملخص التنفيذي مع الإفصاح عن ذلك.
 */
class HawatAnalyst
{
    /** الموضوعات المدعومة، ولكلٍّ منها الكلمات التي تُحيل السؤال إليه. */
    private const TOPICS = [
        'monthly_comparison' => ['هذا الشهر', 'الشهر الماضي', 'مقارنة الشهر', 'الشهري'],
        'top_ports' => ['موانئ', 'ميناء', 'المرافئ'],
        'declining_species' => ['انخفاض', 'الأنواع', 'نوع', 'تراجع'],
        'production_trend' => ['اتجاه', '6 أشهر', 'ستة أشهر', 'أسباب', 'تغير الإنتاج'],
        'sites_monitoring' => ['مواقع', 'موقع الصيد', 'مراقبة', 'ضغط'],
        'discrepancies' => ['فروقات', 'فرق', 'الكابتن', 'الوزن الفعلي'],
        'executive_summary' => ['ملخص', 'تنفيذي', 'إجراءات', 'القطاع'],
    ];

    public const SUGGESTIONS = [
        'كم إنتاج المصيد هذا الشهر مقارنة بالشهر الماضي؟',
        'ما أكثر خمسة موانئ إنتاجًا؟',
        'ما الأنواع التي يظهر عليها انخفاض في الإنتاج؟',
        'ما اتجاه الإنتاج خلال آخر 6 أشهر؟',
        'ما مواقع الصيد التي تحتاج مراقبة؟',
        'أين توجد أعلى فروقات بين إدخال الكابتن والوزن الفعلي؟',
        'أعطني ملخصًا تنفيذيًا للقطاع مع أهم 3 إجراءات مقترحة',
    ];

    /**
     * @return array<string, mixed>
     */
    public function answer(string $question): array
    {
        $topic = $this->route($question);

        $result = match ($topic) {
            'monthly_comparison' => $this->monthlyComparison(),
            'top_ports' => $this->topPorts(),
            'declining_species' => $this->decliningSpecies(),
            'production_trend' => $this->productionTrend(),
            'sites_monitoring' => $this->sitesMonitoring(),
            'discrepancies' => $this->discrepancies(),
            default => $this->executiveSummary(),
        };

        return [
            'topic' => $topic ?? 'executive_summary',
            'question' => $question,
            'unmatched' => $topic === null,
            'data_as_of' => now(),
        ] + $result;
    }

    /**
     * أول موضوع تظهر إحدى كلماته في السؤال، أو null إذا لم يطابق شيئًا.
     */
    private function route(string $question): ?string
    {
        foreach (self::TOPICS as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_stripos($question, $keyword) !== false) {
                    return $topic;
                }
            }
        }

        return null;
    }

    private function monthlyComparison(): array
    {
        $current = $this->monthTons(now());
        $previous = $this->monthTons(now()->subMonth());
        $delta = $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : null;

        $records = CatchRecord::whereBetween('recorded_at', [now()->subMonth()->startOfMonth(), now()->endOfMonth()])->count();

        return [
            'title' => 'مقارنة إنتاج الشهر الحالي بالشهر السابق',
            'direct_answer' => $delta === null
                ? sprintf('بلغ إنتاج الشهر الحالي %s طن. لا يوجد إنتاج مسجّل للشهر السابق تُقارن به النسبة.', $this->num($current))
                : sprintf(
                    'بلغ إنتاج الشهر الحالي %s طن مقابل %s طن في الشهر السابق، بتغيّر قدره %s%%.',
                    $this->num($current),
                    $this->num($previous),
                    ($delta > 0 ? '+' : '').$this->num($delta)
                ),
            'kpis' => [
                ['label' => 'إنتاج الشهر الحالي', 'value' => $this->num($current), 'unit' => 'طن', 'status' => 'good'],
                ['label' => 'إنتاج الشهر السابق', 'value' => $this->num($previous), 'unit' => 'طن', 'status' => 'neutral'],
                ['label' => 'نسبة التغيّر', 'value' => $delta === null ? '—' : $this->num($delta).'%', 'unit' => '', 'status' => ($delta ?? 0) >= 0 ? 'good' : 'critical'],
            ],
            'chart' => $this->trendChart(6),
            'drivers' => $delta === null ? [] : [
                $delta >= 0 ? 'ارتفاع عدد الرحلات المسجّلة أو تحسّن اكتمال إدخال سجلات المصيد.' : 'انخفاض عدد الرحلات المسجّلة أو تأخّر إدخال سجلات المصيد لهذا الشهر.',
                'الشهر الجاري قد يكون غير مكتمل التسجيل، فالمقارنة تتحسن دقتها بعد إقفال الشهر.',
            ],
            'recommendations' => [
                'مطابقة عدد الرحلات المسجّلة مع رحلات الموانئ للتأكد من اكتمال الإدخال.',
                'مراجعة الموانئ التي لم تُسجّل إنتاجًا هذا الشهر في صفحة الإحصاء الميداني.',
            ],
            'confidence' => $previous > 0 ? 'high' : 'low',
            'rows_considered' => $records,
        ];
    }

    private function topPorts(): array
    {
        $records = CatchRecord::with('trip.departurePort')->get();
        $byPort = $records->groupBy(fn ($r) => $r->trip?->departurePort?->name ?? 'غير محدد')
            ->map(fn (Collection $g) => round($g->sum('quantity_kg') / 1000, 2))
            ->sortDesc()
            ->take(5);

        return [
            'title' => 'الموانئ الأعلى إنتاجًا',
            'direct_answer' => $byPort->isEmpty()
                ? 'لا توجد سجلات مصيد مرتبطة بموانئ لعرض ترتيب الموانئ.'
                : sprintf(
                    'أعلى ميناء إنتاجًا هو %s بـ %s طن، ويليه %s. مجموع الموانئ الخمسة الأولى %s طن.',
                    $byPort->keys()->first(),
                    $this->num($byPort->first()),
                    $byPort->keys()->skip(1)->take(2)->implode(' و'),
                    $this->num($byPort->sum())
                ),
            'kpis' => $byPort->take(4)->map(fn ($tons, $port) => [
                'label' => $port, 'value' => $this->num($tons), 'unit' => 'طن', 'status' => 'neutral',
            ])->values()->all(),
            'chart' => ['type' => 'bar', 'title' => 'الإنتاج حسب الميناء', 'unit' => 'طن', 'labels' => $byPort->keys()->all(), 'values' => $byPort->values()->all()],
            'drivers' => [
                'تركّز الأسطول: الموانئ ذات أعلى عدد قوارب نشطة تُسجّل عادةً أعلى إنتاج.',
                'اكتمال الإحصاء الميداني يختلف بين الموانئ، فيؤثر على الترتيب.',
            ],
            'recommendations' => [
                'مقارنة ترتيب الإنتاج بترتيب الامتثال في صفحة مقارنة الأداء.',
                'مراجعة الموانئ ذات الإنتاج المنخفض للتأكد من أنه انخفاض حقيقي لا نقص تسجيل.',
            ],
            'confidence' => $byPort->count() >= 3 ? 'high' : 'medium',
            'rows_considered' => $records->count(),
        ];
    }

    private function decliningSpecies(): array
    {
        $records = CatchRecord::with('species')->whereBetween('recorded_at', [now()->subMonths(6)->startOfMonth(), now()])->get();
        $half = now()->subMonths(3)->startOfMonth();

        $declining = $records->groupBy(fn ($r) => $r->species?->name_ar ?? 'غير محدد')
            ->map(function (Collection $group) use ($half) {
                $recent = (float) $group->filter(fn ($r) => $r->recorded_at >= $half)->sum('quantity_kg');
                $earlier = (float) $group->filter(fn ($r) => $r->recorded_at < $half)->sum('quantity_kg');

                return [
                    'recent_tons' => round($recent / 1000, 2),
                    'earlier_tons' => round($earlier / 1000, 2),
                    'change_pct' => $earlier > 0 ? round(($recent - $earlier) / $earlier * 100, 1) : null,
                ];
            })
            ->filter(fn (array $s) => $s['change_pct'] !== null && $s['change_pct'] < 0)
            ->sortBy('change_pct');

        $stressed = Species::whereIn('status', ['مستغل بالكامل', 'مستغل بإفراط', 'متدهور'])->pluck('name_ar');

        return [
            'title' => 'الأنواع التي يظهر عليها انخفاض في الإنتاج',
            'direct_answer' => $declining->isEmpty()
                ? 'لا يوجد نوع سجّل انخفاضًا بين آخر ثلاثة أشهر والثلاثة التي سبقتها ضمن السجلات المتاحة.'
                : sprintf(
                    'انخفض إنتاج %d نوعًا عند مقارنة آخر ثلاثة أشهر بالثلاثة التي سبقتها، أشدّها %s بنسبة %s%%.',
                    $declining->count(),
                    $declining->keys()->first(),
                    $this->num($declining->first()['change_pct'])
                ),
            'kpis' => $declining->take(4)->map(fn (array $s, string $name) => [
                'label' => $name,
                'value' => $this->num($s['recent_tons']),
                'unit' => 'طن',
                'change_pct' => $s['change_pct'],
                'status' => $s['change_pct'] <= -25 ? 'critical' : 'warning',
            ])->values()->all(),
            'chart' => [
                'type' => 'bar',
                'title' => 'نسبة التغيّر حسب النوع',
                'unit' => '%',
                'labels' => $declining->take(8)->keys()->all(),
                'values' => $declining->take(8)->pluck('change_pct')->all(),
            ],
            'drivers' => array_values(array_filter([
                $stressed->isNotEmpty() ? 'أنواع مصنّفة تحت ضغط في سجل الأنواع: '.$stressed->take(4)->implode('، ').'.' : null,
                'تغيّر موسمي: بعض الأنواع يقل إنتاجها خارج موسمها المعتاد.',
                'تحوّل جهد الصيد إلى أنواع أخرى أعلى سعرًا في السوق.',
            ])),
            'recommendations' => [
                'مراجعة حالة المخزون لهذه الأنواع في صفحة الاستدامة والمخزون.',
                'التحقق من مواسم الصيد المفتوحة لهذه الأنواع قبل تفسير الانخفاض.',
                'متابعة أسعار السوق للأنواع المنخفضة لاستبعاد أثر تحوّل الطلب.',
            ],
            'confidence' => $records->count() > 20 ? 'medium' : 'low',
            'rows_considered' => $records->count(),
        ];
    }

    private function productionTrend(): array
    {
        $chart = $this->trendChart(6);
        $values = collect($chart['values']);
        $first = (float) $values->first();
        $last = (float) $values->last();
        $change = $first > 0 ? round(($last - $first) / $first * 100, 1) : null;

        return [
            'title' => 'اتجاه الإنتاج خلال آخر ستة أشهر',
            'direct_answer' => $change === null
                ? sprintf('بلغ إنتاج الشهر الأخير %s طن، ولا يوجد إنتاج في بداية الفترة تُقاس عليه نسبة التغيّر.', $this->num($last))
                : sprintf(
                    'تحرّك الإنتاج من %s طن في بداية الفترة إلى %s طن في آخرها، بتغيّر إجمالي %s%% ومتوسط شهري %s طن.',
                    $this->num($first),
                    $this->num($last),
                    ($change > 0 ? '+' : '').$this->num($change),
                    $this->num($values->avg())
                ),
            'kpis' => [
                ['label' => 'متوسط شهري', 'value' => $this->num($values->avg()), 'unit' => 'طن', 'status' => 'neutral'],
                ['label' => 'أعلى شهر', 'value' => $this->num($values->max()), 'unit' => 'طن', 'status' => 'good'],
                ['label' => 'أدنى شهر', 'value' => $this->num($values->min()), 'unit' => 'طن', 'status' => 'warning'],
                ['label' => 'التغيّر الإجمالي', 'value' => $change === null ? '—' : $this->num($change).'%', 'unit' => '', 'status' => ($change ?? 0) >= 0 ? 'good' : 'critical'],
            ],
            'chart' => $chart,
            'drivers' => [
                'تغيّر عدد الرحلات المسجّلة شهريًا هو المحرّك الأول لتغيّر الكمية.',
                'فتح المواسم وإغلاقها ينقل جهد الصيد بين الأنواع والمناطق.',
                'اكتمال الإدخال يتفاوت بين الأشهر، والشهر الجاري غالبًا غير مكتمل.',
            ],
            'recommendations' => [
                'إقفال الشهر قبل اعتماد المقارنة الشهرية رسميًا.',
                'مراجعة الأشهر الشاذة صعودًا أو هبوطًا في تقارير الإنتاج.',
            ],
            'confidence' => $values->filter()->count() >= 4 ? 'high' : 'low',
            'rows_considered' => CatchRecord::where('recorded_at', '>=', now()->subMonths(6)->startOfMonth())->count(),
        ];
    }

    private function sitesMonitoring(): array
    {
        $sites = FishingSite::with('port')->get();
        $pressured = $sites->whereIn('pressure_level', ['مرتفع', 'حرج'])->sortByDesc('catch_kg');

        return [
            'title' => 'مواقع الصيد التي تحتاج مراقبة',
            'direct_answer' => $pressured->isEmpty()
                ? sprintf('لا يوجد موقع مصنّف تحت ضغط مرتفع أو حرج من أصل %d موقعًا مسجّلًا.', $sites->count())
                : sprintf(
                    '%d موقعًا من أصل %d مصنّف تحت ضغط مرتفع أو حرج، أعلاها إنتاجًا %s.',
                    $pressured->count(),
                    $sites->count(),
                    $pressured->first()->name
                ),
            'kpis' => [
                ['label' => 'مواقع تحت ضغط', 'value' => $pressured->count(), 'unit' => 'موقع', 'status' => $pressured->count() > 0 ? 'warning' : 'good'],
                ['label' => 'إجمالي المواقع', 'value' => $sites->count(), 'unit' => 'موقع', 'status' => 'neutral'],
                ['label' => 'مصيد المواقع المضغوطة', 'value' => $this->num($pressured->sum('catch_kg') / 1000), 'unit' => 'طن', 'status' => 'neutral'],
            ],
            'chart' => [
                'type' => 'bar',
                'title' => 'المواقع حسب مستوى الضغط',
                'unit' => 'موقع',
                'labels' => $sites->groupBy('pressure_level')->keys()->all(),
                'values' => $sites->groupBy('pressure_level')->map->count()->values()->all(),
            ],
            'drivers' => [
                'تركّز الرحلات على مواقع قريبة من الموانئ الكبرى.',
                'المواقع ذات الإنتاج المرتفع تجذب جهد صيد إضافيًا فيرتفع ضغطها.',
            ],
            'recommendations' => array_values(array_filter([
                $pressured->isNotEmpty() ? 'إدراج المواقع المصنّفة حرجة ضمن خطة الرقابة الميدانية.' : null,
                'مراجعة مستوى الضغط دوريًا في صفحة مواقع الصيد.',
                'ربط مستوى الضغط بمؤشرات الاستدامة قبل اتخاذ قرار تنظيمي.',
            ])),
            'confidence' => $sites->count() > 0 ? 'medium' : 'low',
            'rows_considered' => $sites->count(),
        ];
    }

    private function discrepancies(): array
    {
        $trips = Trip::with('departurePort')->whereNotNull('diff_kg')->where('actual_weight_kg', '>', 0)->get();

        $byPort = $trips->groupBy(fn ($t) => $t->departurePort?->name ?? 'غير محدد')
            ->map(fn (Collection $g) => round($g->avg(fn ($t) => abs((float) $t->diff_kg) / max((float) $t->actual_weight_kg, 1) * 100), 1))
            ->sortDesc();

        $avg = $trips->count() ? round($trips->avg(fn ($t) => abs((float) $t->diff_kg) / max((float) $t->actual_weight_kg, 1) * 100), 1) : 0;

        return [
            'title' => 'الفروقات بين إدخال الكابتن والوزن الفعلي',
            'direct_answer' => $trips->isEmpty()
                ? 'لا توجد رحلات تحمل وزنًا فعليًا وفرقًا مسجّلًا لحساب نسبة الفروقات.'
                : sprintf(
                    'متوسط الفرق %s%% على %d رحلة. أعلى ميناء فرقًا هو %s بمتوسط %s%%.',
                    $this->num($avg),
                    $trips->count(),
                    $byPort->keys()->first(),
                    $this->num($byPort->first())
                ),
            'kpis' => [
                ['label' => 'متوسط الفرق', 'value' => $this->num($avg).'%', 'unit' => '', 'status' => $avg > 10 ? 'critical' : ($avg > 5 ? 'warning' : 'good')],
                ['label' => 'رحلات بها فرق', 'value' => $trips->where('diff_kg', '!=', 0)->count(), 'unit' => 'رحلة', 'status' => 'neutral'],
                ['label' => 'إجمالي الرحلات المقيسة', 'value' => $trips->count(), 'unit' => 'رحلة', 'status' => 'neutral'],
            ],
            'chart' => ['type' => 'bar', 'title' => 'متوسط الفرق حسب الميناء', 'unit' => '%', 'labels' => $byPort->take(8)->keys()->all(), 'values' => $byPort->take(8)->values()->all()],
            'drivers' => [
                'اختلاف طريقة التقدير بين الكابتن والميزان الميداني.',
                'تأخّر الوزن بعد الرسو يغيّر الوزن الفعلي.',
                'تفاوت خبرة موظفي الإحصاء بين الموانئ.',
            ],
            'recommendations' => [
                'إحالة الموانئ الأعلى فرقًا إلى صفحة مراجعة الفروقات.',
                'مراجعة أداء موظفي الإحصاء في تلك الموانئ.',
                'اعتماد حدّ أقصى للفرق المقبول قبل الاعتماد الآلي.',
            ],
            'confidence' => $trips->count() >= 5 ? 'high' : 'low',
            'rows_considered' => $trips->count(),
        ];
    }

    private function executiveSummary(): array
    {
        $kpis = collect(app(ExecutiveKpiService::class)->build()['kpis'])->keyBy('key');
        $pick = fn (string $key) => $kpis[$key] ?? null;

        return [
            'title' => 'الملخص التنفيذي لقطاع المصايد',
            'direct_answer' => sprintf(
                'إجمالي المصيد المعتمد %s طن على %s رحلة، بأسطول نشط قدره %s قارب و%s صياد. متوسط فرق الإحصاء %s%% والمخالفات المسجلة %s.',
                $pick('total_approved_catch')['value'] ?? '—',
                $pick('total_trips')['value'] ?? '—',
                $pick('active_boats')['value'] ?? '—',
                $pick('active_fishers')['value'] ?? '—',
                $pick('avg_statistics_discrepancy')['value'] ?? '—',
                $pick('violations_count')['value'] ?? '—',
            ),
            'kpis' => collect(['total_approved_catch', 'total_trips', 'active_boats', 'approved_catch_share', 'avg_fish_price', 'violations_count', 'avg_statistics_discrepancy', 'traceability_completeness'])
                ->map(fn (string $key) => $pick($key))
                ->filter()
                ->map(fn (array $kpi) => [
                    'label' => $kpi['label'],
                    'value' => $kpi['value'],
                    'unit' => $kpi['unit'],
                    'status' => match ($kpi['tone']) {
                        'success' => 'good',
                        'warning' => 'warning',
                        'danger' => 'critical',
                        default => 'neutral',
                    },
                ])->values()->all(),
            'chart' => $this->trendChart(6),
            'drivers' => [
                'حجم الأسطول النشط وعدد الرحلات هما المحرّك الأول لكمية المصيد.',
                'نسبة المصيد المعتمد تعكس اكتمال دورة الإحصاء لا كمية الإنتاج وحدها.',
                'ارتفاع متوسط فرق الإحصاء يقلّل الثقة في الأرقام قبل الاعتماد.',
            ],
            'recommendations' => [
                'إغلاق الرحلات المعلّقة بانتظار الإحصاء والاعتماد لرفع نسبة المصيد المعتمد.',
                'معالجة الموانئ الأعلى فرقًا في الإحصاء قبل إصدار التقرير الشهري.',
                'متابعة المخالفات المفتوحة في صفحة الرقابة والامتثال حتى إقفالها.',
            ],
            'confidence' => 'high',
            'rows_considered' => Trip::count() + CatchRecord::count() + Boat::count() + Violation::count(),
        ];
    }

    /**
     * إنتاج آخر n شهرًا بالطن، مرتّبًا زمنيًا.
     *
     * @return array{type: string, title: string, unit: string, labels: array<int, string>, values: array<int, float>}
     */
    private function trendChart(int $months): array
    {
        $series = collect(range($months - 1, 0))->map(function (int $back) {
            $month = now()->subMonths($back);

            return [
                'label' => ProductionReportService::MONTHS[(int) $month->format('n')],
                'value' => $this->monthTons($month),
            ];
        });

        return [
            'type' => 'line',
            'title' => 'الإنتاج الشهري',
            'unit' => 'طن',
            'labels' => $series->pluck('label')->all(),
            'values' => $series->pluck('value')->all(),
        ];
    }

    private function monthTons(Carbon $month): float
    {
        return round((float) CatchRecord::whereYear('recorded_at', $month->year)
            ->whereMonth('recorded_at', $month->month)
            ->sum('quantity_kg') / 1000, 2);
    }

    private function num(float|int|null $value): string
    {
        return number_format((float) $value, abs((float) $value) < 100 ? 1 : 0);
    }
}
