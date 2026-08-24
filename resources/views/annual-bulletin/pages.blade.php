@php
    /** أرقام النشرة: كسر واحد للكميات، بلا كسور للأعداد الصحيحة. */
    $n = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals);
    $t = $report['totals'];
    $e = $report['economic'];
    $palette = ['#0759b5', '#0787a6', '#38a169', '#73b341', '#0ea5e9', '#2563eb', '#64748b', '#f59e0b'];

    /** أقصى قيمة في سلسلة — مقام النسب في الأعمدة، ولا يكون صفرًا أبدًا. */
    $peak = fn ($collection, string $key) => max(1, (float) collect($collection)->max($key));
@endphp

<div class="bulletin">
    {{-- الغلاف --}}
    <section class="bp bp-cover">
        <div class="hawat-mark">{{ config('hawat.name') }}</div>
        <p class="eyebrow">ANNUAL FISHERIES BULLETIN · SAUDI ARABIA</p>
        <h1>{{ $edition['title'] }}</h1>
        <p class="lede">{{ $edition['subtitle'] }}</p>
        <div class="cover-year"><span>الإصدار السنوي</span><strong>{{ $report['year'] }}</strong></div>
        <div class="cover-bottom">
            <div>بيانات موثقة</div>
            <div>إحصاءات سنوية</div>
            <div>استدامة ومتابعة</div>
            <div>دعم القرار</div>
        </div>
        <p class="cover-note">إصدار نظام {{ config('hawat.name') }} · السنة الإحصائية {{ $report['year'] }}</p>
    </section>

    {{-- 01 كلمة الإدارة --}}
    <section class="bp">
        <div class="bp-head">
            <div><p class="bp-kicker">HAWAT · MARINE FISHERIES</p><h2>كلمة الإدارة</h2></div>
            <span class="bp-num">01</span>
        </div>
        <p style="font-size:15px;font-weight:600;margin-bottom:14px">بسم الله الرحمن الرحيم</p>
        <p class="b-prose">{{ $edition['management_message'] }}</p>
        <div class="b-sign">{{ $edition['manager_title'] }}</div>
        <div class="b-grid b-3" style="margin-top:32px">
            <div class="tile"><p class="l">سنة النشرة</p><p class="v">{{ $report['year'] }}</p></div>
            <div class="tile"><p class="l">حالة الإصدار</p><p class="v">{{ $edition['status'] }}</p></div>
            <div class="tile"><p class="l">تاريخ التوليد</p><p class="v">{{ $report['generated_at']->format('Y-m-d') }}</p></div>
        </div>
        <div class="bp-foot"><span>النشرة السنوية للمصايد البحرية</span><b>{{ config('hawat.name') }} · HAWAT</b></div>
    </section>

    {{-- 02 الملخص التنفيذي --}}
    <section class="bp">
        <div class="bp-head">
            <div><p class="bp-kicker">HAWAT · MARINE FISHERIES</p><h2>الملخص التنفيذي — {{ $report['year'] }}</h2></div>
            <span class="bp-num">02</span>
        </div>
        <div class="b-grid b-3">
            @foreach ([
                ['إجمالي كمية المصيد', $n($t['catch_tons'], 1).' طن'],
                ['عدد الرحلات', $n($t['trips'])],
                ['القوارب النشطة فعليًا', $n($t['active_boats'])],
                ['الموانئ ذات النشاط', $n($t['ports'])],
                ['المحافظات المغطاة', $n($t['governorates'])],
                ['الصيادون النشطون', $n($t['active_fishers'])],
                ['الأنواع المسجّلة', $n($t['species'])],
                ['متوسط المصيد/رحلة', $n($t['avg_catch_per_trip_kg'], 1).' كجم'],
                ['متوسط سعر الكيلو', $n($e['avg_price_sar_kg'], 2).' ريال'],
            ] as [$label, $value])
                <div class="summary"><p class="l">{{ $label }}</p><p class="v">{{ $value }}</p></div>
            @endforeach
        </div>
        <div class="b-grid b-2" style="margin-top:24px">
            <div class="callout {{ $t['growth_pct'] === null ? 'blue' : ($t['growth_pct'] >= 0 ? 'green' : 'red') }}">
                <p class="l">التغيّر عن السنة السابقة</p>
                <p class="v">{{ $t['growth_pct'] === null ? 'لا توجد بيانات مقارنة' : ($t['growth_pct'] > 0 ? '+' : '').$n($t['growth_pct'], 1).'%' }}</p>
            </div>
            <div class="callout blue">
                <p class="l">قيمة المبيعات التقديرية المسجّلة</p>
                <p class="v">{{ $n($e['estimated_value_sar']) }} ريال</p>
            </div>
        </div>
        <p style="margin-top:24px;font-size:12px;line-height:1.9;color:#64748b">يعرض الملخص أهم مؤشرات النشاط السنوي اعتمادًا على السجلات المتاحة والمعتمدة في النظام للسنة المختارة. المؤشرات التي لا تتوفر لها بيانات كافية تظهر بالقيمة صفر ولا تُقدَّر افتراضيًا.</p>
        <div class="bp-foot"><span>النشرة السنوية للمصايد البحرية</span><b>{{ config('hawat.name') }} · HAWAT</b></div>
    </section>

    {{-- 03 التغطية الجغرافية --}}
    <section class="bp">
        <div class="bp-head">
            <div><p class="bp-kicker">HAWAT · MARINE FISHERIES</p><h2>التغطية الجغرافية للمصايد</h2></div>
            <span class="bp-num">03</span>
        </div>
        <div class="b-grid b-split">
            <div class="panel">
                <div class="panel-head"><span class="panel-dot"></span><h3>مواقع الموانئ ذات الإنتاج</h3></div>
                @include('annual-bulletin.map', ['points' => $report['density_points'], 'height' => '360px'])
            </div>
            <div class="b-grid" style="gap:12px">
                <div class="tile"><p class="l">إجمالي الموانئ المسجّلة</p><p class="v">{{ $n($t['registered_ports']) }}</p></div>
                <div class="tile"><p class="l">موانئ ذات إنتاج خلال السنة</p><p class="v">{{ $n($t['ports']) }}</p></div>
                <div class="tile"><p class="l">المحافظات المغطاة</p><p class="v">{{ $n($t['governorates']) }}</p></div>
                <div class="tile"><p class="l">نقاط الكثافة على الخريطة</p><p class="v">{{ $n($report['density_points']->count()) }}</p></div>
            </div>
        </div>
        <div class="bp-foot"><span>النشرة السنوية للمصايد البحرية</span><b>{{ config('hawat.name') }} · HAWAT</b></div>
    </section>

    {{-- 04 الإنتاج السنوي --}}
    <section class="bp">
        <div class="bp-head">
            <div><p class="bp-kicker">HAWAT · MARINE FISHERIES</p><h2>الإنتاج السنوي</h2></div>
            <span class="bp-num">04</span>
        </div>
        <div class="b-grid b-3">
            <div class="tile"><p class="l">إجمالي كمية المصيد</p><p class="v">{{ $n($t['catch_tons'], 1) }} طن</p></div>
            <div class="tile"><p class="l">المصيد المعتمد</p><p class="v">{{ $n($t['approved_kg'] / 1000, 1) }} طن</p></div>
            <div class="tile"><p class="l">مقارنة بالسنة السابقة</p><p class="v">{{ $t['growth_pct'] === null ? '—' : ($t['growth_pct'] > 0 ? '+' : '').$n($t['growth_pct'], 1).'%' }}</p></div>
        </div>
        <div class="panel" style="margin-top:20px">
            <div class="panel-head"><span class="panel-dot"></span><h3>الإنتاج الشهري (طن)</h3></div>
            @php $monthPeak = $peak($report['monthly'], 'catch_tons'); @endphp
            <div class="columns">
                @foreach ($report['monthly'] as $month)
                    <div class="col">
                        <span style="font-size:8px;color:#0f3056;font-weight:700">{{ $month['catch_tons'] > 0 ? $n($month['catch_tons'], 1) : '' }}</span>
                        <div class="bar" style="height:{{ max(1, $month['catch_tons'] / $monthPeak * 100) }}%"></div>
                        <span class="lbl">{{ mb_substr($month['label'], 0, 3) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="b-grid b-4" style="margin-top:20px">
            @foreach ($report['seasons'] as $season)
                <div class="tile"><p class="l">{{ $season['season'] }}</p><p class="v">{{ $n($season['catch_tons'], 1) }} طن</p><p class="s">{{ $n($season['share_pct'], 1) }}%</p></div>
            @endforeach
        </div>
        <div class="bp-foot"><span>النشرة السنوية للمصايد البحرية</span><b>{{ config('hawat.name') }} · HAWAT</b></div>
    </section>

    {{-- 05 الإنتاج حسب المنطقة --}}
    <section class="bp">
        <div class="bp-head">
            <div><p class="bp-kicker">HAWAT · MARINE FISHERIES</p><h2>الإنتاج حسب المنطقة</h2></div>
            <span class="bp-num">05</span>
        </div>
        @php $regions = $report['by_region']->take(7); @endphp
        <div class="b-grid b-split">
            @include('annual-bulletin.table', [
                'headers' => ['المنطقة', 'كمية المصيد (طن)', 'النسبة', 'الموانئ'],
                'rows' => $regions->map(fn ($r) => [$r['region'], $n($r['catch_tons'], 1), $n($r['share_pct'], 1).'%', $r['ports']]),
            ])
            <div class="panel">
                <div class="panel-head"><span class="panel-dot"></span><h3>توزيع الإنتاج</h3></div>
                @include('annual-bulletin.share', [
                    'items' => $regions->map(fn ($r) => ['label' => $r['region'], 'share' => $r['share_pct'], 'value' => $n($r['share_pct'], 1).'%']),
                    'palette' => $palette,
                ])
            </div>
        </div>
        <div class="bp-foot"><span>النشرة السنوية للمصايد البحرية</span><b>{{ config('hawat.name') }} · HAWAT</b></div>
    </section>

    {{-- 06 الأنواع --}}
    <section class="bp">
        <div class="bp-head">
            <div><p class="bp-kicker">HAWAT · MARINE FISHERIES</p><h2>أنواع الأسماك والأحياء البحرية</h2></div>
            <span class="bp-num">06</span>
        </div>
        @php $species = $report['by_species']->take(10); @endphp
        @include('annual-bulletin.table', [
            'headers' => ['#', 'النوع', 'كمية المصيد (طن)', 'النسبة', 'الرحلات', 'القوارب'],
            'rows' => $species->map(fn ($r, $i) => [$i + 1, $r['species'], $n($r['catch_tons'], 1), $n($r['share_pct'], 1).'%', $r['trips'], $r['boats']]),
        ])
        <div class="panel" style="margin-top:20px">
            <div class="panel-head"><span class="panel-dot"></span><h3>أعلى الأنواع إنتاجًا</h3></div>
            @include('annual-bulletin.bars', [
                'items' => $species->take(7)->map(fn ($r) => ['label' => $r['species'], 'value' => $r['catch_tons'], 'display' => $n($r['catch_tons'], 1).' طن']),
            ])
        </div>
        <div class="bp-foot"><span>النشرة السنوية للمصايد البحرية</span><b>{{ config('hawat.name') }} · HAWAT</b></div>
    </section>

    {{-- 07 الموانئ --}}
    <section class="bp">
        <div class="bp-head">
            <div><p class="bp-kicker">HAWAT · MARINE FISHERIES</p><h2>الموانئ الأعلى إنتاجًا</h2></div>
            <span class="bp-num">07</span>
        </div>
        @php $ports = $report['by_port']->take(10); @endphp
        @include('annual-bulletin.table', [
            'headers' => ['#', 'الميناء', 'المنطقة', 'المحافظة', 'الإنتاج (طن)', 'الرحلات'],
            'rows' => $ports->map(fn ($r, $i) => [$i + 1, $r['port'], $r['region'], $r['governorate'], $n($r['catch_tons'], 1), $r['trips']]),
        ])
        <div class="b-grid b-3" style="margin-top:20px">
            <div class="tile"><p class="l">أعلى ميناء إنتاجًا</p><p class="v">{{ $ports->first()['port'] ?? '—' }}</p><p class="s">{{ $ports->isNotEmpty() ? $n($ports->first()['catch_tons'], 1).' طن' : '' }}</p></div>
            <div class="tile"><p class="l">عدد الموانئ ذات النشاط</p><p class="v">{{ $n($t['ports']) }}</p></div>
            <div class="tile"><p class="l">الموانئ المسجّلة</p><p class="v">{{ $n($t['registered_ports']) }}</p></div>
        </div>
        <div class="bp-foot"><span>النشرة السنوية للمصايد البحرية</span><b>{{ config('hawat.name') }} · HAWAT</b></div>
    </section>

    {{-- 08 القوارب --}}
    <section class="bp">
        <div class="bp-head">
            <div><p class="bp-kicker">HAWAT · MARINE FISHERIES</p><h2>القوارب</h2></div>
            <span class="bp-num">08</span>
        </div>
        <div class="b-grid b-4">
            <div class="tile"><p class="l">القوارب المسجّلة</p><p class="v">{{ $n($t['registered_boats']) }}</p></div>
            <div class="tile"><p class="l">قوارب نشطت خلال السنة</p><p class="v">{{ $n($t['active_boats']) }}</p></div>
            <div class="tile"><p class="l">رخص قوارب سارية</p><p class="v">{{ $n($t['valid_boat_licenses']) }}</p></div>
            <div class="tile"><p class="l">متوسط الإنتاج/قارب</p><p class="v">{{ $t['active_boats'] ? $n($t['catch_tons'] / $t['active_boats'], 1).' طن' : '—' }}</p></div>
        </div>
        <div style="margin-top:20px">
            @include('annual-bulletin.table', [
                'headers' => ['#', 'القارب', 'الميناء', 'الإنتاج (طن)', 'الرحلات'],
                'rows' => $report['top_boats']->take(8)->map(fn ($r, $i) => [$i + 1, $r['boat'], $r['port'], $n($r['catch_tons'], 1), $r['trips']]),
            ])
        </div>
        <div class="bp-foot"><span>النشرة السنوية للمصايد البحرية</span><b>{{ config('hawat.name') }} · HAWAT</b></div>
    </section>

    {{-- 09 الرحلات --}}
    <section class="bp">
        <div class="bp-head">
            <div><p class="bp-kicker">HAWAT · MARINE FISHERIES</p><h2>الرحلات</h2></div>
            <span class="bp-num">09</span>
        </div>
        <div class="b-grid b-4">
            <div class="tile"><p class="l">إجمالي الرحلات</p><p class="v">{{ $n($t['trips']) }}</p></div>
            <div class="tile"><p class="l">سجلات المصيد</p><p class="v">{{ $n($t['trips_records']) }}</p></div>
            <div class="tile"><p class="l">متوسط مدة الرحلة</p><p class="v">{{ $n($report['trips']['avg_duration_hours'], 1) }} ساعة</p></div>
            <div class="tile"><p class="l">متوسط المصيد/رحلة</p><p class="v">{{ $n($t['avg_catch_per_trip_kg'], 1) }} كجم</p></div>
        </div>
        <div class="panel" style="margin-top:20px">
            <div class="panel-head"><span class="panel-dot"></span><h3>الرحلات حسب الشهر</h3></div>
            @php $tripPeak = $peak($report['trips']['by_month'], 'trips'); @endphp
            <div class="columns">
                @foreach ($report['trips']['by_month'] as $month)
                    <div class="col">
                        <span style="font-size:8px;color:#0f3056;font-weight:700">{{ $month['trips'] ?: '' }}</span>
                        <div class="bar" style="height:{{ max(1, $month['trips'] / $tripPeak * 100) }}%"></div>
                        <span class="lbl">{{ mb_substr($month['label'], 0, 3) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="b-grid b-2" style="margin-top:20px">
            @include('annual-bulletin.table', [
                'headers' => ['أداة الصيد', 'عدد الرحلات'],
                'rows' => $report['trips']['by_gear']->take(7)->map(fn ($r) => [$r['gear'], $r['count']]),
            ])
            @include('annual-bulletin.table', [
                'headers' => ['حالة الرحلة', 'العدد'],
                'rows' => $report['trips']['by_status']->map(fn ($r) => [$r['status'], $r['count']]),
            ])
        </div>
        <div class="bp-foot"><span>النشرة السنوية للمصايد البحرية</span><b>{{ config('hawat.name') }} · HAWAT</b></div>
    </section>

    {{-- 10 المؤشرات الاقتصادية --}}
    <section class="bp">
        <div class="bp-head">
            <div><p class="bp-kicker">HAWAT · MARINE FISHERIES</p><h2>المؤشرات الاقتصادية</h2></div>
            <span class="bp-num">10</span>
        </div>
        <div class="b-grid b-2">
            <div class="summary"><p class="l">القيمة التقديرية للمبيعات المسجّلة</p><p class="v">{{ $n($e['estimated_value_sar']) }} ريال</p></div>
            <div class="summary"><p class="l">متوسط سعر الكيلو</p><p class="v">{{ $n($e['avg_price_sar_kg'], 2) }} ريال</p></div>
            <div class="summary"><p class="l">أعلى ميناء من حيث القيمة</p><p class="v">{{ $e['top_port']['name'] ?? '—' }}</p><p class="s">{{ $e['top_port']['name'] ? $n($e['top_port']['value_sar']).' ريال' : '' }}</p></div>
            <div class="summary"><p class="l">أعلى نوع من حيث القيمة</p><p class="v">{{ $e['top_species']['name'] ?? '—' }}</p><p class="s">{{ $e['top_species']['name'] ? $n($e['top_species']['value_sar']).' ريال' : '' }}</p></div>
        </div>
        <div class="b-grid b-3" style="margin-top:20px">
            <div class="tile"><p class="l">عمليات المزاد/السوق</p><p class="v">{{ $n($e['auctions']) }}</p></div>
            <div class="tile"><p class="l">الكمية المباعة</p><p class="v">{{ $n($e['sold_tons'], 1) }} طن</p></div>
            <div class="tile"><p class="l">قيمة/طن منتج</p><p class="v">{{ $t['catch_tons'] ? $n($e['estimated_value_sar'] / $t['catch_tons']).' ريال' : '—' }}</p></div>
        </div>
        <div class="b-note" style="margin-top:24px">المؤشرات الاقتصادية هنا تعتمد على أسعار سجلات المصيد وعمليات الأسواق والمزادات المتوفرة في النظام. إذا لم تُسجَّل عمليات البيع أو الأسعار بالكامل، فالقيمة الظاهرة تمثل البيانات المسجّلة فقط وليست تقديرًا للسوق خارج النظام.</div>
        <div class="bp-foot"><span>النشرة السنوية للمصايد البحرية</span><b>{{ config('hawat.name') }} · HAWAT</b></div>
    </section>

    {{-- 11 مقارنة السنوات --}}
    <section class="bp">
        <div class="bp-head">
            <div><p class="bp-kicker">HAWAT · MARINE FISHERIES</p><h2>مقارنة السنوات</h2></div>
            <span class="bp-num">11</span>
        </div>
        <div class="panel">
            <div class="panel-head"><span class="panel-dot"></span><h3>إجمالي كمية المصيد (طن)</h3></div>
            @php $yearPeak = $peak($report['year_comparison'], 'catch_tons'); @endphp
            <div class="columns" style="height:260px">
                @foreach ($report['year_comparison'] as $row)
                    <div class="col">
                        <span style="font-size:9px;color:#0f3056;font-weight:700">{{ $n($row['catch_tons'], 1) }}</span>
                        <div class="bar {{ $loop->last ? 'last' : '' }}" style="height:{{ max(1, $row['catch_tons'] / $yearPeak * 100) }}%"></div>
                        <span class="lbl">{{ $row['year'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="b-grid b-5" style="margin-top:20px">
            @foreach ($report['year_comparison'] as $row)
                <div class="tile"><p class="l">{{ $row['year'] }}</p><p class="v">{{ $n($row['catch_tons'], 1) }} طن</p></div>
            @endforeach
        </div>
        <div class="bp-foot"><span>النشرة السنوية للمصايد البحرية</span><b>{{ config('hawat.name') }} · HAWAT</b></div>
    </section>

    {{-- 12 خريطة كثافة الإنتاج --}}
    <section class="bp">
        <div class="bp-head">
            <div><p class="bp-kicker">HAWAT · MARINE FISHERIES</p><h2>خريطة كثافة الإنتاج</h2></div>
            <span class="bp-num">12</span>
        </div>
        @include('annual-bulletin.map', ['points' => $report['density_points'], 'height' => '470px'])
        @php
            $maxTons = (float) ($report['density_points']->max('catch_tons') ?: 0);
            $high = $report['density_points']->filter(fn ($p) => $p['catch_tons'] >= $maxTons * 0.66)->count();
            $mid = $report['density_points']->filter(fn ($p) => $p['catch_tons'] >= $maxTons * 0.33 && $p['catch_tons'] < $maxTons * 0.66)->count();
        @endphp
        <div class="b-grid b-3" style="margin-top:16px">
            <div class="tile"><p class="l">كثافة عالية</p><p class="v">{{ $high }}</p></div>
            <div class="tile"><p class="l">كثافة متوسطة</p><p class="v">{{ $mid }}</p></div>
            <div class="tile"><p class="l">مواقع ممثَّلة</p><p class="v">{{ $report['density_points']->count() }}</p></div>
        </div>
        <div class="bp-foot"><span>النشرة السنوية للمصايد البحرية</span><b>{{ config('hawat.name') }} · HAWAT</b></div>
    </section>

    {{-- 13 الصيد العرضي --}}
    <section class="bp">
        <div class="bp-head">
            <div><p class="bp-kicker">HAWAT · MARINE FISHERIES</p><h2>الصيد العرضي والأحياء البحرية</h2></div>
            <span class="bp-num">13</span>
        </div>
        @php $b = $report['bycatch']; @endphp
        <div class="b-grid b-5">
            <div class="tile"><p class="l">الحالات المسجّلة</p><p class="v">{{ $n($b['cases']) }}</p></div>
            <div class="tile"><p class="l">عدد الكائنات</p><p class="v">{{ $n($b['organisms']) }}</p></div>
            <div class="tile"><p class="l">الوزن العرضي</p><p class="v">{{ $n($b['weight_kg'], 1) }} كجم</p></div>
            <div class="tile"><p class="l">نسبة الإطلاق حيًا</p><p class="v">{{ $n($b['release_rate_pct'], 1) }}%</p></div>
            <div class="tile"><p class="l">حالات حسّاسة</p><p class="v">{{ $n($b['sensitive_cases']) }}</p></div>
        </div>
        <div class="b-grid b-split" style="margin-top:20px">
            @include('annual-bulletin.table', [
                'headers' => ['المجموعة', 'الحالات', 'الوزن (كجم)'],
                'rows' => $b['groups']->map(fn ($g) => [$g['group'], $g['cases'], $n($g['weight_kg'], 1)]),
            ])
            <div class="panel">
                <div class="panel-head"><span class="panel-dot"></span><h3>التوزيع حسب المجموعة</h3></div>
                @include('annual-bulletin.share', [
                    'items' => $b['groups']->map(fn ($g) => [
                        'label' => $g['group'],
                        'share' => $b['cases'] > 0 ? $g['cases'] / $b['cases'] * 100 : 0,
                        'value' => $g['cases'],
                    ]),
                    'palette' => $palette,
                ])
            </div>
        </div>
        @if ($b['sensitive_cases'] > 0)
            <div class="b-warn" style="margin-top:20px">
                <div>
                    <b>توجد حالات تحتاج متابعة.</b>
                    <p style="margin-top:4px">راجع صفحة الصيد العرضي ومركز الإنذارات للاطلاع على تفاصيل الكائنات المصنّفة حسّاسة أو التي تتطلب بلاغًا.</p>
                </div>
            </div>
        @endif
        <div class="bp-foot"><span>النشرة السنوية للمصايد البحرية</span><b>{{ config('hawat.name') }} · HAWAT</b></div>
    </section>

    {{-- 14 الجداول الإحصائية --}}
    <section class="bp">
        <div class="bp-head">
            <div><p class="bp-kicker">HAWAT · MARINE FISHERIES</p><h2>الجداول الإحصائية</h2></div>
            <span class="bp-num">14</span>
        </div>
        @php $table = $report['statistical_table']->take(22); @endphp
        @include('annual-bulletin.table', [
            'headers' => ['المنطقة', 'المحافظة', 'الميناء', 'المصيد (طن)', 'الرحلات', 'القوارب', 'الأنواع'],
            'rows' => $table->map(fn ($r) => [$r['region'], $r['governorate'], $r['port'], $n($r['catch_tons'], 1), $r['trips'], $r['boats'], $r['species_count']]),
        ])
        @if ($report['statistical_table']->count() > $table->count())
            <p style="margin-top:12px;font-size:9px;color:#94a3b8">يعرض هذا الملخص أول {{ $table->count() }} صفًا حسب الإنتاج من أصل {{ $report['statistical_table']->count() }}. التفاصيل الكاملة متاحة من صفحة التقارير وتصدير CSV.</p>
        @endif
        <div class="bp-foot"><span>النشرة السنوية للمصايد البحرية</span><b>{{ config('hawat.name') }} · HAWAT</b></div>
    </section>

    {{-- 15 الملاحق والمنهجية --}}
    <section class="bp">
        <div class="bp-head">
            <div><p class="bp-kicker">HAWAT · MARINE FISHERIES</p><h2>الملاحق والمنهجية</h2></div>
            <span class="bp-num">15</span>
        </div>
        <div class="b-grid b-2">
            <div class="appendix"><h3>مصادر البيانات</h3><p>{{ $edition['sources_note'] }}</p></div>
            <div class="appendix"><h3>منهجية جمع البيانات</h3><p>{{ $edition['methodology'] }}</p></div>
            <div class="appendix"><h3>طرق الاحتساب</h3><p>تُجمع كمية المصيد من سجلات المصيد المسجّلة على الرحلات، ويُنسب كل سجل إلى ميناء مغادرة الرحلة ومحافظته ومنطقته. النسب والمقارنات تُحسب آليًا من سجلات السنة المختارة، ولا تُقدَّر قيمة لبيانات غير موجودة.</p></div>
            <div class="appendix"><h3>التعاريف</h3><p>الرحلة: سجل رحلة صيد. القارب النشط: قارب ظهر في سجلات مصيد السنة. الميناء النشط: ميناء لديه سجل مصيد خلال السنة. الحالة الحسّاسة في الصيد العرضي: كائن يقع اسمه ضمن قائمة الكائنات المصنّفة بيئيًا في النظام.</p></div>
        </div>
        <div class="b-note" style="margin-top:24px">
            <b>ملاحظة توثيقية:</b> تُنشأ هذه النشرة آليًا من قاعدة بيانات {{ config('hawat.name') }}. دقة المؤشرات تعتمد على اكتمال تسجيل الرحلات والمصيد والموانئ والأسعار والإحصاء، ولا يختلق التقرير قيمًا للبيانات غير المتوفرة.
        </div>
        <div style="margin-top:32px;text-align:center">
            <div class="hawat-mark">{{ config('hawat.name') }}</div>
            <p style="margin-top:8px;font-size:11px;color:#64748b">النشرة السنوية للمصايد البحرية · {{ $report['year'] }}</p>
        </div>
        <div class="bp-foot"><span>النشرة السنوية للمصايد البحرية</span><b>{{ config('hawat.name') }} · HAWAT</b></div>
    </section>
</div>
