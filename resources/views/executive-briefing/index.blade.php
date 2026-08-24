@extends('layouts.app')

@section('title', 'موجز الإدارة العليا')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'crown'])</div>
            <div>
                <h1>موجز الإدارة العليا</h1>
                <p>لمحة استراتيجية مبسطة لمؤشرات قطاع المصايد البحرية — مع تصدير كامل للبيانات</p>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('stats.executive-briefing.json') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'file-json']) JSON</a>
            <a href="{{ route('stats.executive-briefing.csv') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'file-spreadsheet']) CSV</a>
            <button type="button" class="btn btn-primary" onclick="window.print()">@include('partials.icon', ['name' => 'printer']) طباعة</button>
        </div>
    </div>

    <div class="card" style="margin-bottom:1.25rem;display:flex;flex-wrap:wrap;gap:.75rem;justify-content:space-between;align-items:center">
        <span style="font-size:.75rem;color:hsl(var(--muted-foreground))">نطاق العرض: <strong style="color:hsl(var(--foreground))">{{ $scope }}</strong></span>
        <span style="font-size:.75rem;color:hsl(var(--muted-foreground))">الفترة: آخر 30 يومًا</span>
        <span style="font-size:.75rem;color:hsl(var(--muted-foreground))">تاريخ التوليد: {{ $generatedAt->format('Y-m-d H:i') }}</span>
    </div>

    @if ($strategic->isEmpty())
        <div class="pending-card" style="margin-bottom:1.25rem">
            @include('partials.icon', ['name' => 'bar-chart'])
            <h3>لا توجد مؤشرات معتمدة لعرضها حاليًا</h3>
            <p>لم يجتز أي مؤشر بوابة الاعتماد بعد، فلا يُعرض في الموجز.</p>
        </div>
    @else
        <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
            @foreach ($strategic as $kpi)
                @include('partials.stat-card', ['label' => $kpi['label'], 'value' => $kpi['value'], 'unit' => $kpi['unit'], 'icon' => $kpi['icon'], 'tone' => $kpi['tone']])
            @endforeach
        </div>
    @endif

    <div class="card" style="margin-bottom:1rem">
        <p class="card-title" style="display:flex;align-items:center;gap:.5rem">@include('partials.icon', ['name' => 'line-chart']) الاتجاه الشهري للمصيد</p>
        <p class="card-sub" style="margin-bottom:.75rem">طن — آخر اثني عشر شهرًا</p>
        @if ($trend->sum('value') > 0)
            <div class="chart-wrap" style="height:280px"><canvas id="trendChart"></canvas></div>
        @else
            <p style="padding:4rem 0;text-align:center;font-size:.82rem;color:hsl(var(--muted-foreground))">لا توجد بيانات اتجاه مصيد حاليًا</p>
        @endif
    </div>

    <div class="grid-2" style="margin-bottom:1rem">
        <div class="card">
            <p class="card-title">الإنتاج المعتمد حسب المنطقة</p>
            <p class="card-sub" style="margin-bottom:.75rem">طن</p>
            @if ($byRegion->isNotEmpty())
                <div class="chart-wrap" style="height:300px"><canvas id="regionChart"></canvas></div>
            @else
                <p style="padding:4rem 0;text-align:center;font-size:.82rem;color:hsl(var(--muted-foreground))">لا توجد بيانات إنتاج معتمدة حسب المنطقة</p>
            @endif
        </div>
        <div class="card">
            <p class="card-title">توزيع حالة الأسطول</p>
            <p class="card-sub" style="margin-bottom:.75rem">الرحلات حسب الحالة التشغيلية</p>
            @if ($fleet->isNotEmpty())
                <div class="chart-wrap" style="height:300px"><canvas id="fleetChart"></canvas></div>
            @else
                <p style="padding:4rem 0;text-align:center;font-size:.82rem;color:hsl(var(--muted-foreground))">لا توجد رحلات مسجّلة</p>
            @endif
        </div>
    </div>

    <div class="note-box" style="margin-top:0">
        @include('partials.icon', ['name' => 'shield-check'])
        <div>
            <p class="n-title">مرجع واحد للمؤشرات</p>
            <p class="n-body">يعتمد هذا الموجز تعريفات سجل المؤشرات المعتمدة نفسها المستخدمة في المؤشرات الوطنية واللوحة الرئيسية. لا يُعرض مؤشر لم يجتز الاختبار والاعتماد التقني والاعتماد الرسمي. زرّا JSON و CSV يصدّران المؤشرات والرسوم كما هي معروضة هنا.</p>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = 'Tajawal';
    Chart.defaults.font.size = 11;

    const trendCanvas = document.getElementById('trendChart');
    if (trendCanvas) {
        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: @json($trend->pluck('label')),
                datasets: [{ label: 'المصيد (طن)', data: @json($trend->pluck('value')), borderColor: '#0284c7', backgroundColor: 'rgba(2,132,199,.12)', borderWidth: 2.5, pointRadius: 3, tension: .35, fill: true }]
            },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }

    const regionCanvas = document.getElementById('regionChart');
    if (regionCanvas) {
        new Chart(regionCanvas, {
            type: 'bar',
            data: { labels: @json($byRegion->keys()), datasets: [{ label: 'المصيد (طن)', data: @json($byRegion->values()), backgroundColor: '#0284c7', borderRadius: 4 }] },
            options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } }
        });
    }

    const fleetCanvas = document.getElementById('fleetChart');
    if (fleetCanvas) {
        new Chart(fleetCanvas, {
            type: 'doughnut',
            data: {
                labels: @json($fleet->keys()),
                datasets: [{ data: @json($fleet->values()), backgroundColor: ['#0c4a6e', '#0369a1', '#0284c7', '#38bdf8', '#7dd3fc', '#0891b2', '#06b6d4', '#22d3ee'] }]
            },
            options: { maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } } }
        });
    }
</script>
@endpush
