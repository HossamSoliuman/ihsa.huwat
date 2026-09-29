@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @php
        $now = now();
        $presets = [
            'هذا الشهر' => ['from' => $now->copy()->startOfMonth()->toDateString(), 'to' => $now->toDateString()],
            'الشهر الماضي' => ['from' => $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), 'to' => $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'هذه السنة' => ['from' => $now->copy()->startOfYear()->toDateString(), 'to' => $now->toDateString()],
        ];
        $barHeight = fn ($n) => max(220, $n * 34 + 40);
    @endphp

    @include('panel.owner.reports.partials.web-head')
    @include('panel.owner.reports.partials.web-filter', ['range' => 'dates', 'boat' => 'general', 'presets' => $presets])
    @include('panel.owner.reports.partials.web-kpis')

    @if ($total > 0)
        <div class="grid-2" style="margin-bottom:1.25rem">
            <div class="card">
                @include('partials.section-head', ['icon' => 'layers', 'title' => 'حسب المجموعة'])
                <div class="chart-wrap" style="min-height:{{ $barHeight(count($chart['groups'])) }}px"><canvas dir="ltr" id="groupChart" aria-label="المصروفات حسب المجموعة"></canvas></div>
            </div>
            <div class="card">
                @include('partials.section-head', ['icon' => 'receipt', 'title' => 'أعلى الفئات', 'note' => 'أعلى عشر فئات إنفاقًا'])
                <div class="chart-wrap" style="min-height:{{ $barHeight(count($chart['categories'])) }}px"><canvas dir="ltr" id="categoryChart" aria-label="أعلى فئات المصروفات"></canvas></div>
            </div>
        </div>
    @endif

    @include('panel.owner.reports.partials.web-table', ['table' => $groups, 'icon' => 'layers', 'empty' => 'لا مصروفات في هذه الفترة'])
    @include('panel.owner.reports.partials.web-table', ['table' => array_merge($table, ['title' => 'حسب الفئة']), 'icon' => 'receipt', 'empty' => 'لا مصروفات في هذه الفترة'])
    @include('panel.owner.reports.partials.web-notes')
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    (function () {
        const c = @json($chart);
        if (!document.getElementById('groupChart')) return;
        const money = (v) => Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ر.س';
        const hbar = (labels, data, color) => ({
            type: 'bar',
            data: { labels, datasets: [{ data, backgroundColor: color }] },
            options: { indexAxis: 'y', plugins: { legend: { display: false }, tooltip: { callbacks: { label: (x) => money(x.raw) } } }, scales: { x: { beginAtZero: true } } },
        });
        new Chart(document.getElementById('groupChart'), hbar(c.groups, c.group_totals, hawatChart.accent));
        new Chart(document.getElementById('categoryChart'), hbar(c.categories, c.category_totals, hawatChart.accent));
    })();
</script>
@endpush
