@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @php
        $now = now();
        $presets = [
            'هذا الشهر' => ['from' => $now->copy()->startOfMonth()->toDateString(), 'to' => $now->toDateString()],
            'آخر 3 أشهر' => ['from' => $now->copy()->subMonthsNoOverflow(2)->startOfMonth()->toDateString(), 'to' => $now->toDateString()],
            'هذه السنة' => ['from' => $now->copy()->startOfYear()->toDateString(), 'to' => $now->toDateString()],
        ];
        $chartRows = collect($table['rows'])->take(-20)->values();
    @endphp

    @include('panel.owner.reports.partials.web-head')
    @include('panel.owner.reports.partials.web-filter', ['range' => 'dates', 'boat' => true, 'presets' => $presets])
    @include('panel.owner.reports.partials.web-kpis')

    @if ($chartRows->isNotEmpty())
        <div class="card" style="margin-bottom:1.25rem">
            @include('partials.section-head', ['icon' => 'bar-chart', 'title' => 'ربح كل رحلة', 'note' => $chartRows->count() < count($table['rows']) ? 'آخر 20 رحلة في الفترة' : 'رحلات الفترة'])
            <div class="chart-wrap" style="min-height:280px"><canvas dir="ltr" id="tripChart" aria-label="ربح كل رحلة"></canvas></div>
        </div>
    @endif

    @include('panel.owner.reports.partials.web-table', ['table' => array_merge($table, ['title' => 'الرحلات']), 'icon' => 'route', 'empty' => 'لا رحلات غادرت في هذه الفترة'])
    @include('panel.owner.reports.partials.web-notes')
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    (function () {
        const el = document.getElementById('tripChart');
        if (!el) return;
        const rows = @json($chartRows);
        const money = (v) => Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ر.س';
        new Chart(el, {
            type: 'bar',
            data: {
                labels: rows.map((r) => r.trip),
                datasets: [{ label: 'الربح', data: rows.map((r) => r.profit), backgroundColor: rows.map((r) => r.profit < 0 ? hawatChart.status.critical : hawatChart.accent) }],
            },
            options: {
                plugins: { legend: { display: false }, tooltip: { callbacks: {
                    title: (x) => rows[x[0].dataIndex].trip + ' — ' + rows[x[0].dataIndex].boat,
                    label: (x) => 'الربح ' + money(x.raw),
                    afterLabel: (x) => 'صافي الإيراد ' + money(rows[x.dataIndex].revenue) + ' — مصروفات ' + money(rows[x.dataIndex].expenses),
                } } },
            },
        });
    })();
</script>
@endpush
