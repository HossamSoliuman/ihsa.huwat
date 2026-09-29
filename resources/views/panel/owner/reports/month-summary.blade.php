@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @php
        $f = $figures;
        $prev = $month->subMonth();
        $next = $month->addMonth();
    @endphp

    @include('panel.owner.reports.partials.web-head')

    <form method="GET" action="{{ route('panel.owner.reports.show', $key) }}" class="filter-bar" style="margin-bottom:1.25rem;display:flex;flex-wrap:wrap;align-items:flex-end;gap:.65rem">
        <a class="btn btn-outline" href="{{ route('panel.owner.reports.show', ['report' => $key, 'period' => $prev->format('Y-m')]) }}">@include('partials.icon', ['name' => 'chevron-right']) {{ \App\Models\MonthClosing::label($prev->year, $prev->month) }}</a>
        <label class="field"><span>الشهر</span><input class="input" type="month" name="period" value="{{ $month->format('Y-m') }}" max="{{ now()->format('Y-m') }}" dir="ltr"></label>
        <button class="btn btn-primary">@include('partials.icon', ['name' => 'search']) عرض</button>
        @if ($next->lessThanOrEqualTo(now()->startOfMonth()))
            <a class="btn btn-outline" href="{{ route('panel.owner.reports.show', ['report' => $key, 'period' => $next->format('Y-m')]) }}">{{ \App\Models\MonthClosing::label($next->year, $next->month) }} @include('partials.icon', ['name' => 'chevron-left'])</a>
        @endif
        <span style="margin-inline-start:auto">
            @if ($status === 'closed')
                <a href="{{ route('panel.owner.month-closings.show', $closing->id) }}" class="badge badge-ok">@include('partials.icon', ['name' => 'lock']) مُغلق — من لقطة الإغلاق</a>
            @else
                <span class="badge badge-warn">@include('partials.icon', ['name' => 'lock-open']) {{ $status === 'current' ? 'الشهر الجاري' : 'غير مُغلق' }} — أرقام حيّة</span>
            @endif
        </span>
    </form>

    @include('panel.owner.reports.partials.web-kpis', ['kpis' => [
        ['label' => 'صافي الإيراد', 'value' => $f['revenue'], 'format' => 'money', 'icon' => 'coins'],
        ['label' => 'المصروفات', 'value' => $f['total_expenses'], 'format' => 'money', 'icon' => 'receipt'],
        ['label' => 'الربح التشغيلي', 'value' => $f['operating'], 'format' => 'money', 'icon' => 'trending-up'],
        ['label' => 'نصيب الطاقم', 'value' => $f['crew_pool'], 'format' => 'money', 'icon' => 'users'],
        ['label' => 'صافيك', 'value' => $f['owner_net'], 'format' => 'money', 'strong' => true, 'icon' => 'calculator'],
    ]])

    <div class="grid-2" style="margin-bottom:1.25rem;align-items:start">
        <div class="card">
            @include('partials.section-head', ['icon' => 'scale', 'title' => 'قائمة الشهر', 'note' => $label])
            @include('panel.owner.reports.partials.web-statement')
        </div>
        <div class="card">
            @include('partials.section-head', ['icon' => 'fish', 'title' => 'صافيك حسب الصنف', 'note' => 'ما بيع في الشهر'])
            @if (count($species['rows']) > 0)
                <div class="chart-wrap" style="min-height:{{ max(220, min(count($species['rows']), 10) * 34 + 40) }}px"><canvas dir="ltr" id="speciesChart" aria-label="صافي المالك حسب الصنف"></canvas></div>
            @else
                <p style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا مبيعات في هذا الشهر</p>
            @endif
        </div>
    </div>

    @include('panel.owner.reports.partials.web-table', ['table' => $boats, 'icon' => 'ship', 'empty' => 'لا نشاط للقوارب في هذا الشهر'])
    @include('panel.owner.reports.partials.web-table', ['table' => $species, 'icon' => 'fish', 'empty' => 'لا مبيعات في هذا الشهر'])
    @include('panel.owner.reports.partials.web-notes')
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    (function () {
        const el = document.getElementById('speciesChart');
        if (!el) return;
        const rows = @json(array_slice($species['rows'], 0, 10));
        new Chart(el, {
            type: 'bar',
            data: { labels: rows.map((r) => r.species), datasets: [{ label: 'صافيك (ر.س)', data: rows.map((r) => r.net), backgroundColor: hawatChart.accent }] },
            options: {
                indexAxis: 'y',
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => Number(c.raw).toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' ر.س — ' + rows[c.dataIndex].kg + ' كجم' } } },
                scales: { x: { beginAtZero: true } },
            },
        });
    })();
</script>
@endpush
