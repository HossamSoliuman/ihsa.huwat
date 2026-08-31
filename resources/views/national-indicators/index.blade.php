@extends('layouts.app')

@section('title', 'المؤشرات الوطنية')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'bar-chart'])</div>
            <div>
                <h1>المؤشرات الوطنية المعتمدة</h1>
            </div>
        </div>
        <div class="actions">
            <span class="badge badge-ok">{{ $total }}/15 معتمد</span>
        </div>
    </div>

    <div class="card">
        <div class="gov-grid">
            <div class="gov-box"><p class="g-label">معتمد رسميًا</p><p class="g-value">{{ $total }}</p></div>
            <div class="gov-box"><p class="g-label">معتمد تقنيًا</p><p class="g-value">{{ $total }}</p></div>
            <div class="gov-box"><p class="g-label">بوابة الاعتماد</p><p class="g-value">{{ $total }}</p></div>
            <div class="gov-box"><p class="g-label">المعروض في اللوحة</p><p class="g-value">{{ $total }}</p></div>
        </div>
        <p style="margin-top:.75rem;font-size:.72rem;color:hsl(var(--muted-foreground))">نطاق المستخدم الحالي: <strong style="color:hsl(var(--foreground))">المملكة</strong></p>
    </div>

    @foreach ($groups as $group)
        <section>
            @include('partials.section-head', ['icon' => 'scale', 'title' => $group['title']])
            <div class="stat-grid cols-4">
                @foreach ($group['items'] as $kpi)
                    @include('partials.stat-card', ['label' => $kpi['label'], 'value' => $kpi['value'], 'unit' => $kpi['unit'], 'icon' => $kpi['icon'], 'tone' => $kpi['tone']])
                @endforeach
            </div>
        </section>
    @endforeach

    @include('partials.section-head', ['icon' => 'map', 'title' => 'الإنتاج حسب المنطقة'])

    {{-- سقط الصندوق الشارح، فالرسم يأخذ الصفّ كلّه بدل أن يترك ثلثه فارغًا. --}}
    <div class="card">
        <p class="card-title">الإنتاج المعتمد حسب المنطقة</p>
        <p class="card-sub" style="margin-bottom:.7rem">آخر 30 يومًا — طن</p>
        <div class="chart-wrap" style="min-height:300px"><canvas id="regionChart"></canvas></div>
    </div>

    <div class="card">
        <p class="card-title" style="margin-bottom:.7rem">السلسلة الجغرافية للمملكة</p>
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
            @foreach (['المملكة', 'المنطقة', 'المحافظة', 'الميناء / المرسى', 'موقع الصيد'] as $label)
                <div class="hier-chip">{{ $label }}</div>
                @if (! $loop->last)<span style="color:hsl(var(--muted-foreground))">←</span>@endif
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    new Chart(document.getElementById('regionChart'), {
        type: 'bar',
        data: {
            labels: @json($byRegion->keys()),
            datasets: [{ label: 'المصيد المعتمد (طن)', data: @json($byRegion->values()), backgroundColor: hawatChart.accent }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
    });
</script>
@endpush