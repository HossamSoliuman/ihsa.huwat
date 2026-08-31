@extends('layouts.app')

@section('title', 'موجز الإدارة العليا')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'crown'])</div>
            <div>
                <h1>موجز الإدارة العليا</h1>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('stats.executive-briefing.json') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'file-json']) JSON</a>
            <a href="{{ route('stats.executive-briefing.csv') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'file-spreadsheet']) CSV</a>
            <button type="button" class="btn btn-primary" onclick="window.print()">@include('partials.icon', ['name' => 'printer']) طباعة</button>
        </div>
    </div>

    <div class="card" style="display:flex;flex-direction:row;flex-wrap:wrap;gap:.5rem 1.5rem;align-items:center;padding:.6rem .95rem">
        <span style="font-size:.72rem;color:hsl(var(--muted-foreground))">نطاق العرض: <strong style="color:hsl(var(--foreground))">{{ $scope }}</strong></span>
        <span style="font-size:.72rem;color:hsl(var(--muted-foreground))">الفترة: <strong style="color:hsl(var(--foreground))">آخر 30 يومًا</strong></span>
        <span style="font-size:.72rem;color:hsl(var(--muted-foreground))">تاريخ التوليد: <strong style="color:hsl(var(--foreground))">{{ $generatedAt->format('Y-m-d H:i') }}</strong></span>
    </div>

    @include('partials.section-head', ['icon' => 'gauge', 'title' => 'المؤشرات الاستراتيجية'])

    @if ($strategic->isEmpty())
        <div class="pending-card">
            @include('partials.icon', ['name' => 'bar-chart'])
            <h3>لا توجد مؤشرات معتمدة لعرضها حاليًا</h3>
            <p>لم يجتز أي مؤشر بوابة الاعتماد بعد، فلا يُعرض في الموجز.</p>
        </div>
    @else
        <div class="stat-grid cols-4">
            @foreach ($strategic as $kpi)
                @include('partials.stat-card', ['label' => $kpi['label'], 'value' => $kpi['value'], 'unit' => $kpi['unit'], 'icon' => $kpi['icon'], 'tone' => $kpi['tone']])
            @endforeach
        </div>
    @endif

    @include('partials.section-head', ['icon' => 'line-chart', 'title' => 'الاتجاه والتوزيع'])

    {{-- الاتجاه يأخذ عمودين والتوزيع عمودًا: صفٌّ واحد بلا خانة فارغة. --}}
    <div class="grid-3">
        <div class="card span-2">
            <p class="card-title" style="display:flex;align-items:center;gap:.4rem">@include('partials.icon', ['name' => 'line-chart']) الاتجاه الشهري للمصيد</p>
            <p class="card-sub" style="margin-bottom:.7rem">طن — آخر اثني عشر شهرًا</p>
            @if ($trend->sum('value') > 0)
                <div class="chart-wrap" style="min-height:290px"><canvas id="trendChart"></canvas></div>
            @else
                <p style="padding:4rem 0;text-align:center;font-size:.8rem;color:hsl(var(--muted-foreground))">لا توجد بيانات اتجاه مصيد حاليًا</p>
            @endif
        </div>
        <div class="card">
            <p class="card-title" style="display:flex;align-items:center;gap:.4rem">@include('partials.icon', ['name' => 'sailboat']) توزيع حالة الأسطول</p>
            <p class="card-sub" style="margin-bottom:.7rem">الرحلات حسب الحالة التشغيلية</p>
            @if ($fleet->isNotEmpty())
                <div class="chart-wrap" style="min-height:290px"><canvas id="fleetChart"></canvas></div>
            @else
                <p style="padding:4rem 0;text-align:center;font-size:.8rem;color:hsl(var(--muted-foreground))">لا توجد رحلات مسجّلة</p>
            @endif
        </div>
    </div>

    <div class="card">
        <p class="card-title" style="display:flex;align-items:center;gap:.4rem">@include('partials.icon', ['name' => 'map']) الإنتاج المعتمد حسب المنطقة</p>
        <p class="card-sub" style="margin-bottom:.7rem">طن</p>
        @if ($byRegion->isNotEmpty())
            <div class="chart-wrap" style="min-height:{{ max(170, $byRegion->count() * 40 + 70) }}px"><canvas id="regionChart"></canvas></div>
        @else
            <p style="padding:4rem 0;text-align:center;font-size:.8rem;color:hsl(var(--muted-foreground))">لا توجد بيانات إنتاج معتمدة حسب المنطقة</p>
        @endif
    </div>

@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    const trendCanvas = document.getElementById('trendChart');
    if (trendCanvas) {
        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: @json($trend->pluck('label')),
                datasets: [{ label: 'المصيد (طن)', data: @json($trend->pluck('value')), borderColor: hawatChart.accent, backgroundColor: hawatChart.accentFill, fill: true }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }

    const regionCanvas = document.getElementById('regionChart');
    if (regionCanvas) {
        new Chart(regionCanvas, {
            type: 'bar',
            data: { labels: @json($byRegion->keys()), datasets: [{ label: 'المصيد (طن)', data: @json($byRegion->values()), backgroundColor: hawatChart.accent }] },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true }, y: { grid: { display: false } } } }
        });
    }

    const fleetCanvas = document.getElementById('fleetChart');
    if (fleetCanvas) {
        const fleetLabels = @json($fleet->keys());
        new Chart(fleetCanvas, {
            type: 'doughnut',
            data: {
                labels: fleetLabels,
                datasets: [{ data: @json($fleet->values()), backgroundColor: hawatChart.colors(fleetLabels.length) }]
            },
            options: { cutout: '58%', plugins: { legend: { position: 'bottom' } } }
        });
    }
</script>
@endpush
