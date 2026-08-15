@extends('layouts.app')

@section('title', 'المؤشرات الوطنية')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'bar-chart'])</div>
            <div>
                <h1>المؤشرات الوطنية المعتمدة</h1>
                <p>السجل التنفيذي الرسمي لمؤشرات قطاع المصايد البحرية — نفس التعريفات المستخدمة في حوات AI والتحليلات</p>
            </div>
        </div>
        <div class="actions">
            <span class="badge badge-ok">{{ $total }}/15 معتمد</span>
        </div>
    </div>

    <div class="card" style="margin-bottom:1.25rem">
        <div class="gov-grid">
            <div class="gov-box"><p class="g-label">معتمد رسميًا</p><p class="g-value">{{ $total }}</p></div>
            <div class="gov-box"><p class="g-label">معتمد تقنيًا</p><p class="g-value">{{ $total }}</p></div>
            <div class="gov-box"><p class="g-label">بوابة الاعتماد</p><p class="g-value">{{ $total }}</p></div>
            <div class="gov-box"><p class="g-label">المعروض في اللوحة</p><p class="g-value">{{ $total }}</p></div>
        </div>
        <p style="margin-top:.75rem;font-size:.72rem;color:hsl(var(--muted-foreground))">نطاق المستخدم الحالي: <strong style="color:hsl(var(--foreground))">المملكة</strong></p>
    </div>

    @foreach ($groups as $group)
        <section style="margin-bottom:1.25rem">
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;color:hsl(var(--primary))">
                @include('partials.icon', ['name' => 'scale'])
                <h2 style="font-size:.875rem;font-weight:700;color:hsl(var(--foreground))">{{ $group['title'] }}</h2>
            </div>
            <div class="stat-grid cols-4">
                @foreach ($group['items'] as $kpi)
                    @include('partials.stat-card', ['label' => $kpi['label'], 'value' => $kpi['value'], 'unit' => $kpi['unit'], 'icon' => $kpi['icon'], 'tone' => $kpi['tone']])
                @endforeach
            </div>
        </section>
    @endforeach

    <div class="grid-3">
        <div class="card span-2">
            <p class="card-title">الإنتاج المعتمد حسب المنطقة</p>
            <p class="card-sub" style="margin-bottom:.75rem">آخر 30 يومًا — طن</p>
            <div class="chart-wrap" style="height:320px"><canvas id="regionChart"></canvas></div>
        </div>
        <div class="note-box" style="margin-top:0;flex-direction:column;align-items:flex-start">
            @include('partials.icon', ['name' => 'shield-check'])
            <div>
                <p class="n-title" style="font-size:1rem">مرجع واحد للمؤشرات</p>
                <p class="n-body" style="font-size:.82rem">أي مؤشر يتغير تعريفه أو إصداره يخرج تلقائيًا من العرض حتى يجتاز الاختبار والاعتماد التقني والاعتماد الرسمي للإصدار الجديد.</p>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:1rem">
        <p class="card-title" style="margin-bottom:.75rem">السلسلة الجغرافية للمملكة</p>
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
            @foreach (['المملكة', 'المنطقة', 'المحافظة', 'الميناء / المرسى', 'موقع الصيد'] as $label)
                <div class="hier-chip">{{ $label }}</div>
                @if (! $loop->last)<span style="color:hsl(var(--muted-foreground))">←</span>@endif
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = 'Tajawal';
    Chart.defaults.font.size = 11;
    new Chart(document.getElementById('regionChart'), {
        type: 'bar',
        data: {
            labels: @json($byRegion->keys()),
            datasets: [{ label: 'المصيد المعتمد (طن)', data: @json($byRegion->values()), backgroundColor: '#0284c7', borderRadius: 4 }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
</script>
@endpush