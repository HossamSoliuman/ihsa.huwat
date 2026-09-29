@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @include('panel.owner.reports.partials.web-head')

    <form method="GET" action="{{ route('panel.owner.reports.show', $key) }}" class="filter-bar" style="margin-bottom:1.25rem;display:flex;flex-wrap:wrap;align-items:flex-end;gap:.65rem">
        <label class="field"><span>السنة</span>
            <select class="select" name="year" onchange="this.form.submit()">
                @foreach ($years as $y)<option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>@endforeach
            </select>
        </label>
        <span style="margin-inline-start:auto;font-size:.82rem;color:hsl(var(--muted-foreground))">الأشهر المُغلقة: <b class="num">{{ $closed }}</b> من <b class="num">{{ $counted }}</b></span>
    </form>

    @include('panel.owner.reports.partials.web-kpis', ['kpis' => array_merge($kpis, [
        ['label' => 'هامش صافيك', 'value' => $margin, 'format' => 'pct', 'icon' => 'gauge'],
    ])])

    <div class="card" style="margin-bottom:1.25rem">
        @include('partials.section-head', ['icon' => 'bar-chart', 'title' => 'السنة شهرًا بشهر', 'note' => 'الإيراد، والتكاليف (مصروفات القوارب والعامة والإهلاك)، وصافيك'])
        <div class="chart-wrap" style="min-height:320px"><canvas dir="ltr" id="yearChart" aria-label="الإيراد والتكاليف وصافي المالك لكل شهر"></canvas></div>
    </div>

    @include('panel.owner.reports.partials.web-table', ['table' => array_merge($table, ['title' => 'الأشهر']), 'icon' => 'calendar-days'])
    @include('panel.owner.reports.partials.web-notes')
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    (function () {
        const c = @json($chart);
        const money = (v) => Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ر.س';
        new Chart(document.getElementById('yearChart'), {
            data: {
                labels: c.labels,
                datasets: [
                    { type: 'bar', label: 'الإيراد', data: c.revenue, backgroundColor: hawatChart.categorical[0], order: 2 },
                    { type: 'bar', label: 'التكاليف', data: c.costs, backgroundColor: hawatChart.categorical[1], order: 2 },
                    { type: 'line', label: 'صافيك', data: c.owner_net, borderColor: hawatChart.categorical[2], backgroundColor: hawatChart.categorical[2], spanGaps: false, order: 1 },
                ],
            },
            options: { plugins: { tooltip: { callbacks: { label: (x) => x.dataset.label + ': ' + money(x.raw) } } }, scales: { x: { ticks: { autoSkip: false } } } },
        });
    })();
</script>
@endpush
