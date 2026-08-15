@extends('layouts.app')

@section('title', 'سلسلة الإمداد')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'layers'])</div>
            <div>
                <h1>سلسلة الإمداد</h1>
                <p>مسار المصيد من الاعتماد حتى البيع في الأسواق ونِسب الفاقد</p>
            </div>
        </div>
    </div>

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @foreach ($stages as $stage)
            @include('partials.stat-card', ['label' => $stage['label'], 'value' => number_format($stage['value']), 'unit' => 'كجم', 'icon' => $stage['icon'], 'tone' => $stage['tone']])
        @endforeach
    </div>

    <div class="card" style="margin-bottom:1.25rem">
        <p class="card-title" style="margin-bottom:1rem">مراحل السلسلة</p>
        @php $max = max(1, $approved, $offered, $sold); @endphp
        @foreach ($stages as $stage)
            <div style="margin-bottom:.85rem">
                <div style="display:flex;justify-content:space-between;font-size:.75rem;margin-bottom:.3rem">
                    <span style="font-weight:600">{{ $stage['label'] }}</span>
                    <span style="color:hsl(var(--muted-foreground))">{{ number_format($stage['value']) }} كجم</span>
                </div>
                <div class="progress"><div style="width:{{ round($stage['value'] / $max * 100) }}%;background:hsl(var(--primary))"></div></div>
            </div>
        @endforeach
        <div class="gov-grid" style="margin-top:1rem">
            <div class="gov-box"><p class="g-label">نسبة الوصول للأسواق</p><p class="g-value">{{ $toMarketRate }}%</p></div>
            <div class="gov-box"><p class="g-label">نسبة البيع</p><p class="g-value">{{ $sellRate }}%</p></div>
            <div class="gov-box"><p class="g-label">المتبقي بالمخزون</p><p class="g-value">{{ number_format(max(0, $offered - $sold)) }}</p></div>
            <div class="gov-box"><p class="g-label">غير معروض بالأسواق</p><p class="g-value">{{ number_format(max(0, $approved - $offered)) }}</p></div>
        </div>
    </div>

    <div class="card">
        <p class="card-title">الكميات المباعة حسب السوق</p>
        <div class="chart-wrap" style="height:300px;margin-top:.75rem"><canvas id="shareChart"></canvas></div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = 'Tajawal';
    new Chart(document.getElementById('shareChart'), {
        type: 'bar',
        data: { labels: @json($marketShare->keys()), datasets: [{ label: 'كجم مباعة', data: @json($marketShare->values()), backgroundColor: '#0284c7', borderRadius: 4 }] },
        options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } }
    });
</script>
@endpush