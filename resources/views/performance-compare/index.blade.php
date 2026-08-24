@extends('layouts.app')

@section('title', 'مقارنة الأداء')

@php
    /** لون الكمية بالنسبة للمتوسط: أخضر أعلى بكثير، أحمر أقل بكثير، أزرق حول المتوسط. */
    $catchColor = fn ($value) => $value >= $avgCatch * 1.15 ? '#059669' : ($value <= $avgCatch * 0.7 ? '#e11d48' : '#0284c7');
    $complianceColor = fn ($value) => $value === null ? '#94a3b8' : ($value >= 90 ? '#059669' : ($value >= 75 ? '#d97706' : '#e11d48'));
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'gauge'])</div>
            <div>
                <h1>مقارنة أداء الموانئ والمحافظات</h1>
                <p>تحليل مقارن لكميات المصيد ونسبة الامتثال في لوحة واحدة مع إبراز الفروقات</p>
            </div>
        </div>
        <div class="actions">
            <div class="seg">
                <a href="{{ route('stats.performance-compare', ['view' => 'governorate', 'top' => $topN]) }}" class="{{ $view === 'governorate' ? 'is-active' : '' }}">
                    @include('partials.icon', ['name' => 'building']) المحافظات
                </a>
                <a href="{{ route('stats.performance-compare', ['view' => 'port', 'top' => $topN]) }}" class="{{ $view === 'port' ? 'is-active' : '' }}">
                    @include('partials.icon', ['name' => 'anchor']) الموانئ
                </a>
            </div>
            <form method="GET">
                <input type="hidden" name="view" value="{{ $view }}">
                <select class="select" name="top" onchange="this.form.submit()" style="width:auto">
                    @foreach ($topOptions as $option)
                        <option value="{{ $option }}" @selected($topN === $option)>أعلى {{ $option }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        <div class="gap-card primary">
            <p class="g-label">متوسط المصيد (طن)</p>
            <p class="g-value">{{ number_format($avgCatch) }}</p>
        </div>
        <div class="gap-card {{ $catchGap > $avgCatch ? 'danger' : 'warning' }}">
            <p class="g-label">فروق المصيد (طن)</p>
            <p class="g-value">{{ number_format($catchGap) }}</p>
            <p class="g-hint">الأعلى − الأقل</p>
        </div>
        <div class="gap-card {{ $avgCompliance >= 85 ? 'success' : 'warning' }}">
            <p class="g-label">متوسط الامتثال</p>
            <p class="g-value">{{ $avgCompliance }}%</p>
        </div>
        <div class="gap-card {{ $complianceGap > 20 ? 'danger' : 'warning' }}">
            <p class="g-label">فروق الامتثال</p>
            <p class="g-value">{{ $complianceGap }}%</p>
            <p class="g-hint">الأعلى − الأقل</p>
        </div>
    </div>

    <div class="card" style="margin-bottom:1rem">
        <p class="card-title">{{ $view === 'governorate' ? 'المصيد والامتثال حسب المحافظة' : 'المصيد والامتثال حسب الميناء' }}</p>
        <p class="card-sub" style="margin-bottom:.75rem">الأعمدة الزرقاء = المصيد (طن) — الأعمدة الفيروزية = نسبة الامتثال %</p>
        @if ($rows->isEmpty())
            <p style="padding:4rem 0;text-align:center;font-size:.82rem;color:hsl(var(--muted-foreground))">لا توجد بيانات كافية للمقارنة</p>
        @else
            <div class="chart-wrap" style="height:360px"><canvas id="compareChart"></canvas></div>
        @endif
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ $view === 'governorate' ? 'المحافظة' : 'الميناء' }}</th>
                    <th>المصيد (طن)</th>
                    <th>المصيد مقابل المتوسط</th>
                    <th>الامتثال %</th>
                    <th>الامتثال مقابل المتوسط</th>
                    <th>الرحلات الشهرية</th>
                    <th>القوارب النشطة</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $index => $row)
                    @php
                        $catchDelta = $avgCatch > 0 ? round(($row['catch'] - $avgCatch) / $avgCatch * 100) : 0;
                        $complianceDelta = $row['compliance'] !== null && $avgCompliance > 0 ? $row['compliance'] - $avgCompliance : null;
                        $isTopCatch = $row['name'] === $topCatchName;
                        $isLowCatch = $row['name'] === $lowCatchName;
                        $isTopCompliance = $row['name'] === $topComplianceName;
                        $isLowCompliance = $row['name'] === $lowComplianceName;
                    @endphp
                    <tr>
                        <td style="font-weight:700;color:hsl(var(--muted-foreground))">{{ $index + 1 }}</td>
                        <td>
                            <p style="font-weight:600">{{ $row['name'] }}</p>
                            <p style="font-size:11px;color:hsl(var(--muted-foreground))">{{ $row['sub'] }}</p>
                        </td>
                        <td>
                            <span style="display:inline-flex;align-items:center;gap:.35rem;font-weight:700;color:{{ $catchColor($row['catch']) }}">
                                @if ($isTopCatch)@include('partials.icon', ['name' => 'trophy'])@endif
                                {{ number_format($row['catch'], 1) }}
                            </span>
                        </td>
                        <td>@include('performance-compare.delta', ['delta' => $catchDelta, 'extreme' => $isTopCatch || $isLowCatch, 'low' => $isLowCatch, 'suffix' => '%'])</td>
                        <td>
                            @if ($row['compliance'] === null)
                                <span style="font-size:.72rem;color:hsl(var(--muted-foreground))">—</span>
                            @else
                                <span class="score-chip" style="background:{{ $complianceColor($row['compliance']) }}">{{ $row['compliance'] }}%</span>
                            @endif
                        </td>
                        <td>
                            @if ($complianceDelta === null)
                                <span style="font-size:.72rem;color:hsl(var(--muted-foreground))">—</span>
                            @else
                                @include('performance-compare.delta', ['delta' => $complianceDelta, 'extreme' => $isTopCompliance || $isLowCompliance, 'low' => $isLowCompliance, 'suffix' => ' نقطة'])
                            @endif
                        </td>
                        <td>{{ number_format($row['trips']) }}</td>
                        <td>{{ number_format($row['boats']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد بيانات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-top:1rem">
        <p style="font-size:.75rem;line-height:1.9;color:hsl(var(--muted-foreground))">
            الأخضر: أعلى من المتوسط بوضوح · الأزرق: قريب من المتوسط · الأحمر: أقل من المتوسط بوضوح.
            نسبة الامتثال تُحسب من الرحلات نفسها: الرحلة ممتثلة إذا اعتُمدت ولم يُسجَّل عليها فرق بين إدخال الكابتن والوزن الفعلي،
            والرحلات المعلّقة تُحسب غير ممتثلة لأنها لم تُغلق بعد. امتثال المحافظة متوسط موانئها.
        </p>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = 'Tajawal';
    Chart.defaults.font.size = 11;

    const canvas = document.getElementById('compareChart');
    if (canvas) {
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: @json($rows->pluck('name')),
                datasets: [
                    { label: 'المصيد (طن)', data: @json($rows->pluck('catch')), backgroundColor: @json($rows->map(fn ($r) => $catchColor($r['catch']))), borderRadius: 4, yAxisID: 'y' },
                    { label: 'الامتثال %', data: @json($rows->map(fn ($r) => $r['compliance'] ?? 0)), backgroundColor: '#0891b2', borderRadius: 4, yAxisID: 'y1' }
                ]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
                scales: {
                    y: { beginAtZero: true, position: 'right', title: { display: true, text: 'طن' } },
                    y1: { beginAtZero: true, max: 100, position: 'left', grid: { drawOnChartArea: false }, title: { display: true, text: '%' } }
                }
            }
        });
    }
</script>
@endpush
