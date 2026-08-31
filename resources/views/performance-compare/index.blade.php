@extends('layouts.app')

@section('title', 'مقارنة الأداء')

@php
    /*
     * حالة القيمة لا لونها: الاسم يُترجَم إلى لون في مكان واحد — متغيّرات
     * `--st-*` للنصّ، و`hawatChart.status` للرسوم — فيتبع اللون الوضعَ الفاتح
     * والداكن بلا تكرار قيمٍ في الصفحة.
     */
    $catchStatus = fn ($value) => $value >= $avgCatch * 1.15 ? 'good' : ($value <= $avgCatch * 0.7 ? 'critical' : 'neutral');
    $complianceStatus = fn ($value) => $value === null ? 'none' : ($value >= 90 ? 'good' : ($value >= 75 ? 'warn' : 'critical'));
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'gauge'])</div>
            <div>
                <h1>مقارنة أداء الموانئ والمحافظات</h1>
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

    @include('partials.section-head', ['icon' => 'scale', 'title' => 'خلاصة الفروقات'])

    <div class="stat-grid cols-4">
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

    @include('partials.section-head', ['icon' => 'bar-chart', 'title' => $view === 'governorate' ? 'المصيد والامتثال حسب المحافظة' : 'المصيد والامتثال حسب الميناء'])

    {{--
        رسمان لا رسم واحد بمحورين: الطن والنسبة مقياسان مختلفان، وجمعهما على
        محورين في إطار واحد يجعل ارتفاع العمودين قابلًا للمقارنة وهو ليس كذلك.
    --}}
    <div class="grid-2">
        <div class="card">
            <p class="card-title">المصيد</p>
            <p class="card-sub" style="margin-bottom:.7rem">طن — أخضر أعلى من المتوسط، أحمر أقل منه</p>
            @if ($rows->isEmpty())
                <p style="padding:4rem 0;text-align:center;font-size:.8rem;color:hsl(var(--muted-foreground))">لا توجد بيانات كافية للمقارنة</p>
            @else
                <div class="chart-wrap" style="min-height:{{ max(200, $rows->count() * 34 + 70) }}px"><canvas id="catchChart"></canvas></div>
            @endif
        </div>
        <div class="card">
            <p class="card-title">نسبة الامتثال</p>
            <p class="card-sub" style="margin-bottom:.7rem">% — من صفر إلى مئة</p>
            @if ($rows->isEmpty())
                <p style="padding:4rem 0;text-align:center;font-size:.8rem;color:hsl(var(--muted-foreground))">لا توجد بيانات كافية للمقارنة</p>
            @else
                <div class="chart-wrap" style="min-height:{{ max(200, $rows->count() * 34 + 70) }}px"><canvas id="complianceChart"></canvas></div>
            @endif
        </div>
    </div>

    @include('partials.section-head', ['icon' => 'clipboard', 'title' => 'الجدول التفصيلي'])

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
                            <span style="display:inline-flex;align-items:center;gap:.35rem;font-weight:700;color:var(--st-{{ $catchStatus($row['catch']) }})">
                                @if ($isTopCatch)@include('partials.icon', ['name' => 'trophy'])@endif
                                {{ number_format($row['catch'], 1) }}
                            </span>
                        </td>
                        <td>@include('performance-compare.delta', ['delta' => $catchDelta, 'extreme' => $isTopCatch || $isLowCatch, 'low' => $isLowCatch, 'suffix' => '%'])</td>
                        <td>
                            @if ($row['compliance'] === null)
                                <span style="font-size:.72rem;color:hsl(var(--muted-foreground))">—</span>
                            @else
                                <span class="score-chip" style="background:var(--st-{{ $complianceStatus($row['compliance']) }})">{{ $row['compliance'] }}%</span>
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

    <div class="card">
        <p style="font-size:.75rem;line-height:1.9;color:hsl(var(--muted-foreground))">
            الأخضر: أعلى من المتوسط بوضوح · الأزرق: قريب من المتوسط · الأحمر: أقل من المتوسط بوضوح.
            نسبة الامتثال تُحسب من الرحلات نفسها: الرحلة ممتثلة إذا اعتُمدت ولم يُسجَّل عليها فرق بين إدخال الكابتن والوزن الفعلي،
            والرحلات المعلّقة تُحسب غير ممتثلة لأنها لم تُغلق بعد. امتثال المحافظة متوسط موانئها.
        </p>
    </div>
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    const labels = @json($rows->pluck('name'));

    const catchCanvas = document.getElementById('catchChart');
    if (catchCanvas) {
        new Chart(catchCanvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{ label: 'المصيد (طن)', data: @json($rows->pluck('catch')), backgroundColor: hawatChart.statusColors(@json($rows->map(fn ($r) => $catchStatus($r['catch'])))) }]
            },
            options: {
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, title: { display: true, text: 'طن' } }, y: { grid: { display: false } } }
            }
        });
    }

    const complianceCanvas = document.getElementById('complianceChart');
    if (complianceCanvas) {
        new Chart(complianceCanvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{ label: 'الامتثال %', data: @json($rows->map(fn ($r) => $r['compliance'] ?? 0)), backgroundColor: hawatChart.statusColors(@json($rows->map(fn ($r) => $complianceStatus($r['compliance'])))) }]
            },
            options: {
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, max: 100, title: { display: true, text: '%' } }, y: { grid: { display: false } } }
            }
        });
    }
</script>
@endpush
