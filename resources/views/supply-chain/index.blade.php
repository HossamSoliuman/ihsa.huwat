@extends('layouts.app')

@section('title', 'سلسلة الإمداد')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'layers'])</div>
            <div>
                <h1>سلسلة الإمداد</h1>
            </div>
        </div>
    </div>

    @include('partials.section-head', ['icon' => 'layers', 'title' => 'مراحل السلسلة'])

    <div class="stat-grid cols-4">
        @foreach ($stages as $stage)
            @include('partials.stat-card', ['label' => $stage['label'], 'value' => number_format($stage['value']), 'unit' => 'كجم', 'icon' => $stage['icon'], 'tone' => $stage['tone']])
        @endforeach
    </div>

    <div class="card">
        <p class="card-title">مراحل السلسلة</p>
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
        <div class="gov-grid">
            <div class="gov-box"><p class="g-label">نسبة الوصول للأسواق</p><p class="g-value">{{ $toMarketRate }}%</p></div>
            <div class="gov-box"><p class="g-label">نسبة البيع</p><p class="g-value">{{ $sellRate }}%</p></div>
            <div class="gov-box"><p class="g-label">المتبقي بالمخزون</p><p class="g-value">{{ number_format(max(0, $offered - $sold)) }}</p></div>
            <div class="gov-box"><p class="g-label">غير معروض بالأسواق</p><p class="g-value">{{ number_format(max(0, $approved - $offered)) }}</p></div>
        </div>
    </div>

    @include('partials.section-head', ['icon' => 'hammer', 'title' => 'التوزيع على الأسواق'])

    <div class="card">
        <p class="card-title">الكميات المباعة حسب السوق</p>
        <p class="card-sub" style="margin-bottom:.7rem">كيلوجرام</p>
        <div class="chart-wrap" style="min-height:{{ max(170, $marketShare->count() * 40 + 70) }}px"><canvas id="shareChart"></canvas></div>
    </div>
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    new Chart(document.getElementById('shareChart'), {
        type: 'bar',
        data: { labels: @json($marketShare->keys()), datasets: [{ label: 'كجم مباعة', data: @json($marketShare->values()), backgroundColor: hawatChart.accent }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true }, y: { grid: { display: false } } } }
    });
</script>
@endpush