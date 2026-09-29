@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @php
        $f = $figures;
        $now = now();
        $presets = [
            'هذا الشهر' => ['from' => $now->format('Y-m'), 'to' => $now->format('Y-m')],
            'الشهر الماضي' => ['from' => $now->copy()->subMonthNoOverflow()->format('Y-m'), 'to' => $now->copy()->subMonthNoOverflow()->format('Y-m')],
            'آخر 3 أشهر' => ['from' => $now->copy()->subMonthsNoOverflow(2)->format('Y-m'), 'to' => $now->format('Y-m')],
            'هذه السنة' => ['from' => $now->format('Y').'-01', 'to' => $now->format('Y-m')],
            'السنة الماضية' => ['from' => ($now->year - 1).'-01', 'to' => ($now->year - 1).'-12'],
        ];
    @endphp

    @include('panel.owner.reports.partials.web-head')
    @include('panel.owner.reports.partials.web-filter', ['range' => 'months', 'boat' => true, 'presets' => $presets])

    @include('panel.owner.reports.partials.web-kpis', ['kpis' => [
        ['label' => 'صافي الإيراد', 'value' => $f['revenue'], 'format' => 'money', 'icon' => 'coins'],
        ['label' => 'المصروفات', 'value' => $f['total_expenses'], 'format' => 'money', 'icon' => 'receipt'],
        ['label' => 'الإهلاك المحمَّل', 'value' => $f['depreciation_charged'] + $f['general_depreciation'], 'format' => 'money', 'icon' => 'trending-down'],
        ['label' => 'الربح التشغيلي', 'value' => $f['operating'], 'format' => 'money', 'icon' => 'trending-up'],
        ['label' => 'نصيب الطاقم', 'value' => $f['crew_pool'], 'format' => 'money', 'icon' => 'users'],
        ['label' => $boat ? 'نصيبك من القارب' : 'صافيك', 'value' => $f['owner_net'], 'format' => 'money', 'strong' => true, 'icon' => 'calculator'],
    ]])

    @if ($open_count > 0)
        <div class="card" style="margin-bottom:1.25rem;font-size:.82rem;display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
            <span style="color:var(--st-warn);display:inline-flex">@include('partials.icon', ['name' => 'alert-triangle'])</span>
            <span>{{ $open_count }} من أشهر الفترة غير مُغلق — أرقامه حيّة كما في معاينة إغلاقه وقد تتغير.</span>
            <a href="{{ route('panel.owner.month-closings') }}" class="btn btn-outline" style="margin-inline-start:auto">@include('partials.icon', ['name' => 'lock']) إغلاق الشهر</a>
        </div>
    @endif

    <div class="grid-2" style="margin-bottom:1.25rem;align-items:start">
        <div class="card">
            @include('partials.section-head', ['icon' => 'scale', 'title' => 'القائمة', 'note' => $period.($boat ? ' — '.$boat->name : ' — كل الأسطول')])
            @include('panel.owner.reports.partials.web-statement')
        </div>
        <div class="card">
            @include('partials.section-head', ['icon' => 'bar-chart', 'title' => 'شهرًا بشهر', 'note' => 'الإيراد والتكاليف (مصروفات + إهلاك) وصافيك'])
            @if (count($months['rows']) > 0)
                <div class="chart-wrap" style="min-height:320px"><canvas dir="ltr" id="plChart" aria-label="الإيراد والتكاليف وصافي المالك شهريًا"></canvas></div>
            @else
                <p style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا أشهر في الفترة</p>
            @endif
        </div>
    </div>

    @include('panel.owner.reports.partials.web-table', ['table' => $months, 'icon' => 'calendar-days'])
    @include('panel.owner.reports.partials.web-notes')
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    (function () {
        const el = document.getElementById('plChart');
        if (!el) return;
        const rows = @json($months['rows']);
        const money = (v) => Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ر.س';
        new Chart(el, {
            data: {
                labels: rows.map((r) => r.month),
                datasets: [
                    { type: 'bar', label: 'الإيراد', data: rows.map((r) => r.revenue), backgroundColor: hawatChart.categorical[0], order: 2 },
                    { type: 'bar', label: 'التكاليف', data: rows.map((r) => r.expenses + r.depreciation), backgroundColor: hawatChart.categorical[1], order: 2 },
                    { type: 'line', label: 'صافيك', data: rows.map((r) => r.owner_net), borderColor: hawatChart.categorical[2], backgroundColor: hawatChart.categorical[2], order: 1 },
                ],
            },
            options: { plugins: { tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + money(c.raw) } } }, scales: { x: { ticks: { autoSkip: false } } } },
        });
    })();
</script>
@endpush
