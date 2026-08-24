@extends('layouts.app')

@section('title', 'التحليلات والمؤشرات')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'line-chart'])</div>
            <div>
                <h1>التحليلات والمؤشرات</h1>
                <p>مقارنة الإنتاج بين منطقتين أو نوعين أو ميناءين، مع ترتيب أعلى عشرة</p>
            </div>
        </div>
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
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

    @if ($comparable)
        <div class="stat-grid" style="margin-bottom:1rem">
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

        <div class="card" style="margin-bottom:1rem">
            <p class="card-title" style="margin-bottom:.75rem">مقارنة بصرية</p>
            @foreach ([[$first, $firstValue], [$second, $secondValue]] as [$label, $value])
                <div style="margin-bottom:.875rem">
                    <div class="legend-row">
                        <span style="font-weight:500">{{ $label }}</span>
                        <span style="color:hsl(var(--muted-foreground))">{{ number_format($value, 1) }} طن</span>
                    </div>
                    <div class="progress">
                        <div style="width:{{ max($firstValue, $secondValue) > 0 ? min(100, $value / max($firstValue, $secondValue) * 100) : 0 }}%;background:linear-gradient(270deg,#0369a1,#06b6d4)"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="pending-card" style="margin-bottom:1rem">
            @include('partials.icon', ['name' => 'arrow-left-right'])
            <h3>اختر عنصرين للمقارنة</h3>
            <p>حدّد نوع المقارنة ثم العنصرين، وستظهر الكميات والفرق بينهما ونسبته.</p>
        </div>
    @endif

    <div class="card">
        <p class="card-title">أعلى عشرة — {{ $types[$type] }}</p>
        <p class="card-sub" style="margin-bottom:.75rem">المصيد بالطن</p>
        @if ($top->isNotEmpty())
            <div class="chart-wrap" style="height:320px"><canvas id="topChart"></canvas></div>
        @else
            <p style="padding:3rem 0;text-align:center;font-size:.82rem;color:hsl(var(--muted-foreground))">لا توجد بيانات</p>
        @endif
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = 'Tajawal';
    Chart.defaults.font.size = 11;

    const canvas = document.getElementById('topChart');
    if (canvas) {
        new Chart(canvas, {
            type: 'bar',
            data: { labels: @json($top->keys()), datasets: [{ label: 'المصيد (طن)', data: @json($top->values()), backgroundColor: '#0284c7', borderRadius: 4 }] },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }
</script>
@endpush
