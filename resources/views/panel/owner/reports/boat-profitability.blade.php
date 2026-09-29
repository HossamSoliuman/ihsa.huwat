@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @php
        $now = now();
        $presets = [
            'هذا الشهر' => ['from' => $now->format('Y-m'), 'to' => $now->format('Y-m')],
            'آخر 3 أشهر' => ['from' => $now->copy()->subMonthsNoOverflow(2)->format('Y-m'), 'to' => $now->format('Y-m')],
            'هذه السنة' => ['from' => $now->format('Y').'-01', 'to' => $now->format('Y-m')],
        ];
    @endphp

    @include('panel.owner.reports.partials.web-head')
    @include('panel.owner.reports.partials.web-filter', ['range' => 'months', 'presets' => $presets])
    @include('panel.owner.reports.partials.web-kpis')

    @if (count($chart['labels']) > 0)
        <div class="card" style="margin-bottom:1.25rem">
            @include('partials.section-head', ['icon' => 'bar-chart', 'title' => 'صافي الإيراد والتكاليف والربح لكل قارب', 'note' => $period])
            <div class="chart-wrap" style="min-height:{{ max(240, count($chart['labels']) * 58 + 60) }}px"><canvas dir="ltr" id="boatChart" aria-label="ربحية كل قارب"></canvas></div>
        </div>
    @endif

    @include('panel.owner.reports.partials.web-table', ['table' => array_merge($table, ['title' => 'القوارب']), 'icon' => 'ship', 'empty' => 'لا نشاط للقوارب في هذه الفترة'])

    <div class="card" style="margin-bottom:1.25rem">
        @include('partials.section-head', ['icon' => 'calculator', 'title' => 'من نصيبك إلى صافيك'])
        <dl style="display:grid;grid-template-columns:1fr auto;gap:.35rem 1rem;font-size:.85rem;max-width:28rem">
            <dt>نصيبك من القوارب</dt><dd class="num">{{ number_format($table['totals']['owner_share'] ?? 0, 2) }}</dd>
            <dt>− مصروفات عامة (بلا قارب)</dt><dd class="num">{{ number_format($general['expenses'], 2) }}</dd>
            <dt>− إهلاك الأصول العامة</dt><dd class="num">{{ number_format($general['depreciation'], 2) }}</dd>
            <dt style="font-weight:800">صافيك</dt><dd class="num" style="font-weight:800">{{ number_format($owner_net, 2) }} ر.س</dd>
        </dl>
    </div>

    @include('panel.owner.reports.partials.web-notes')
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    (function () {
        const el = document.getElementById('boatChart');
        if (!el) return;
        const c = @json($chart);
        const money = (v) => Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ر.س';
        new Chart(el, {
            type: 'bar',
            data: {
                labels: c.labels,
                datasets: [
                    { label: 'صافي الإيراد', data: c.revenue, backgroundColor: hawatChart.categorical[0] },
                    { label: 'التكاليف', data: c.costs, backgroundColor: hawatChart.categorical[1] },
                    { label: 'صافي الربح', data: c.net, backgroundColor: hawatChart.categorical[2] },
                ],
            },
            options: { indexAxis: 'y', plugins: { tooltip: { callbacks: { label: (x) => x.dataset.label + ': ' + money(x.raw) } } } },
        });
    })();
</script>
@endpush
