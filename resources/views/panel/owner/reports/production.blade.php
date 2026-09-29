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
    @endphp

    @include('panel.owner.reports.partials.web-head')
    @include('panel.owner.reports.partials.web-filter', ['range' => 'dates', 'boat' => true, 'presets' => $presets])
    @include('panel.owner.reports.partials.web-kpis')

    @if (count($chart['labels']) > 0)
        <div class="card" style="margin-bottom:1.25rem">
            @include('partials.section-head', ['icon' => 'fish', 'title' => 'المصيد والمباع', 'note' => 'أعلى عشرة أصناف مصيدًا — بالكيلوغرام'])
            <div class="chart-wrap" style="min-height:{{ max(240, count($chart['labels']) * 44 + 60) }}px"><canvas dir="ltr" id="speciesChart" aria-label="المصيد والمباع لكل صنف"></canvas></div>
        </div>
    @endif

    @include('panel.owner.reports.partials.web-table', ['table' => array_merge($table, ['title' => 'الأصناف']), 'icon' => 'fish', 'empty' => 'لا مصيد لرحلات هذه الفترة'])
    @include('panel.owner.reports.partials.web-notes')
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    (function () {
        const el = document.getElementById('speciesChart');
        if (!el) return;
        const c = @json($chart);
        const kg = (v) => Number(v).toLocaleString('en-US', { maximumFractionDigits: 1 }) + ' كجم';
        new Chart(el, {
            type: 'bar',
            data: {
                labels: c.labels,
                datasets: [
                    { label: 'المصيد', data: c.caught, backgroundColor: hawatChart.categorical[0] },
                    { label: 'المباع', data: c.sold, backgroundColor: hawatChart.categorical[2] },
                ],
            },
            options: { indexAxis: 'y', plugins: { tooltip: { callbacks: { label: (x) => x.dataset.label + ': ' + kg(x.raw) } } }, scales: { x: { beginAtZero: true } } },
        });
    })();
</script>
@endpush
