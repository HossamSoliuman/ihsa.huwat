@extends('layouts.app')

@section('title', 'التحليلات والمؤشرات')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'line-chart'])</div>
            <div>
                <h1>التحليلات والمؤشرات</h1>
            </div>
        </div>
    </div>

    <form method="GET" class="filter-bar">
        <label class="field"><span>نوع المقارنة</span>
            <select class="select" name="type" onchange="this.form.first.value=''; this.form.second.value=''; this.form.submit()">
                @foreach ($types as $key => $label)
                    <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="field"><span>الأول</span>
            <select class="select" name="first" onchange="this.form.submit()" style="min-width:11rem">
                <option value="">اختر...</option>
                @foreach ($options as $name => $value)
                    <option value="{{ $name }}" @selected($first === $name)>{{ $name }}</option>
                @endforeach
            </select>
        </label>
        <span class="field" style="padding-bottom:.6rem;color:hsl(var(--muted-foreground))">@include('partials.icon', ['name' => 'arrow-left-right'])</span>
        <label class="field"><span>الثاني</span>
            <select class="select" name="second" onchange="this.form.submit()" style="min-width:11rem">
                <option value="">اختر...</option>
                @foreach ($options as $name => $value)
                    <option value="{{ $name }}" @selected($second === $name)>{{ $name }}</option>
                @endforeach
            </select>
        </label>
        <a href="{{ route('stats.analytics', ['type' => $type]) }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    @include('partials.section-head', ['icon' => 'arrow-left-right', 'title' => 'المقارنة'])

    @if ($comparable)
        <div class="stat-grid">
            @include('partials.stat-card', ['label' => $first, 'value' => number_format($firstValue, 1), 'unit' => 'طن', 'icon' => 'fish', 'tone' => 'primary'])
            @include('partials.stat-card', ['label' => $second, 'value' => number_format($secondValue, 1), 'unit' => 'طن', 'icon' => 'fish', 'tone' => 'info'])
            <div class="gap-card {{ $difference >= 0 ? 'success' : 'danger' }}">
                <p class="g-label">الفرق</p>
                <p class="g-value" style="display:flex;align-items:center;gap:.5rem">
                    @include('partials.icon', ['name' => $difference >= 0 ? 'trending-up' : 'trending-down'])
                    {{ number_format($difference, 1) }}
                </p>
                <p class="g-hint">{{ $differencePct > 0 ? '+' : '' }}{{ $differencePct }}% مقارنة بالأول</p>
            </div>
        </div>

        <div class="card">
            <p class="card-title" style="margin-bottom:.7rem">مقارنة بصرية</p>
            @foreach ([[$first, $firstValue], [$second, $secondValue]] as [$label, $value])
                <div>
                    <div class="legend-row">
                        <span style="font-weight:500">{{ $label }}</span>
                        <span style="color:hsl(var(--muted-foreground))">{{ number_format($value, 1) }} طن</span>
                    </div>
                    <div class="progress">
                        <div style="width:{{ max($firstValue, $secondValue) > 0 ? min(100, $value / max($firstValue, $secondValue) * 100) : 0 }}%;background:hsl(var(--primary))"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="pending-card">
            @include('partials.icon', ['name' => 'arrow-left-right'])
            <h3>اختر عنصرين للمقارنة</h3>
            <p>حدّد نوع المقارنة ثم العنصرين، وستظهر الكميات والفرق بينهما ونسبته.</p>
        </div>
    @endif

    @include('partials.section-head', ['icon' => 'trophy', 'title' => 'أعلى عشرة'])

    <div class="card">
        <p class="card-title">أعلى عشرة — {{ $types[$type] }}</p>
        <p class="card-sub" style="margin-bottom:.7rem">المصيد بالطن</p>
        @if ($top->isNotEmpty())
            <div class="chart-wrap" style="min-height:{{ max(170, $top->count() * 40 + 70) }}px"><canvas id="topChart"></canvas></div>
        @else
            <p style="padding:3rem 0;text-align:center;font-size:.82rem;color:hsl(var(--muted-foreground))">لا توجد بيانات</p>
        @endif
    </div>
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    const canvas = document.getElementById('topChart');
    if (canvas) {
        new Chart(canvas, {
            type: 'bar',
            data: { labels: @json($top->keys()), datasets: [{ label: 'المصيد (طن)', data: @json($top->values()), backgroundColor: hawatChart.accent }] },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true }, y: { grid: { display: false } } } }
        });
    }
</script>
@endpush
