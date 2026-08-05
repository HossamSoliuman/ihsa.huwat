@extends('layouts.information-admin')

@section('title', 'لوحة مؤشرات مركز المعلومات')

@section('content')
<header class="info-dashboard-header">
    <h1>لوحة مؤشرات مركز المعلومات</h1>
    <time datetime="{{ $generatedAt->toIso8601String() }}">آخر تحديث {{ $generatedAt->diffForHumans() }}</time>
</header>

<form class="info-dashboard-filters" method="get" action="{{ route('information.admin.dashboard') }}">
    <div class="info-field">
        <label for="dashboard-range">الفترة</label>
        <select id="dashboard-range" name="range">
            @foreach ($rangeOptions as $rangeValue => $rangeLabel)
                <option value="{{ $rangeValue }}" @selected($filters['range'] === (string) $rangeValue)>{{ $rangeLabel }}</option>
            @endforeach
        </select>
    </div>
    <div class="info-field">
        <label for="dashboard-port">الميناء</label>
        <select id="dashboard-port" name="port_id">
            <option value="">كل الموانئ</option>
            @foreach ($ports as $port)
                <option value="{{ $port->id }}" @selected($filters['port_id'] === $port->id)>{{ $port->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="info-field">
        <label for="dashboard-region">المنطقة</label>
        <select id="dashboard-region" name="region_id">
            <option value="">كل المناطق</option>
            @foreach ($regions as $region)
                <option value="{{ $region->id }}" @selected($filters['region_id'] === $region->id)>{{ $region->name }}</option>
            @endforeach
        </select>
    </div>
    <button class="info-button info-button-primary" type="submit">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16M7 12h10m-7 7h4"></path></svg>
        <span>تطبيق الفلاتر</span>
    </button>
    @if ($filters['range'] !== '30' || $filters['port_id'] || $filters['region_id'])
        <a class="info-button info-button-ghost" href="{{ route('information.admin.dashboard') }}">إعادة الضبط</a>
    @endif
</form>

<x-information.section-head title="نظرة عامة" icon="grid" />

<section class="info-dashboard-kpis" aria-label="المؤشرات الرئيسية">
    <x-information.chart.stat-tile
        :label="$hero['label']" :value="$hero['value']" :suffix="$hero['suffix']"
        :delta="$hero['delta']" :delta-good="$hero['delta_good']" :delta-suffix="$hero['delta_suffix']"
        :icon="$hero['icon']" :tone="$hero['tone']" :href="$hero['href']" />

    @foreach ($kpis as $kpi)
        <x-information.chart.stat-tile
            :label="$kpi['label']" :value="$kpi['value']" :suffix="$kpi['suffix']"
            :delta="$kpi['delta']" :delta-good="$kpi['delta_good']" :delta-suffix="$kpi['delta_suffix']"
            :icon="$kpi['icon']" :tone="$kpi['tone']" :href="$kpi['href']" />
    @endforeach
</section>

<x-information.section-head title="التحليلات" icon="chart" />

<section class="info-dashboard-grid info-dashboard-grid-analytics">
    <x-information.chart.figure
        id="flow-trend" title="الطلبات عبر الزمن" icon="trend" :legend="$trend['series']">
        <x-information.chart.line :series="$trend['series']" />
        <x-slot:table>
            <table class="info-chart-table">
                <thead><tr><th>الفترة</th><th>الوارد</th><th>المنجز</th></tr></thead>
                <tbody>
                    @foreach ($trend['series'][0]['values'] as $index => $point)
                        <tr><th>{{ $point['label'] }}</th><td>{{ $point['value'] }}</td><td>{{ $trend['series'][1]['values'][$index]['value'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </x-slot:table>
    </x-information.chart.figure>

    <x-information.chart.figure
        id="pipeline" title="حالة الطلبات" icon="layers" :legend="$pipeline['segments']">
        <x-information.chart.stacked-bar :bars="[$pipeline]" />
        <x-slot:table>
            <table class="info-chart-table">
                <thead><tr><th>الحالة</th><th>الطلبات</th></tr></thead>
                <tbody>
                    @foreach ($pipeline['segments'] as $segment)
                        <tr><th><a href="{{ $segment['href'] }}">{{ $segment['label'] }}</a></th><td>{{ $segment['value'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </x-slot:table>
    </x-information.chart.figure>

    <x-information.chart.figure id="reviewers" title="أداء المراجعين" icon="users">
        @if (count($reviewers) === 0)
            <p class="info-chart-empty">لا توجد قرارات منسوبة إلى مراجعين ضمن الفترة.</p>
        @else
            @php $reviewerMaxMedian = max(1, (float) collect($reviewers)->max('median_days')); @endphp
            <div class="info-chart-table-scroll">
                <table class="info-chart-table info-reviewer-table">
                    <thead><tr><th>المراجع</th><th>القرارات</th><th>متوسط الأيام</th><th>الاعتماد</th><th>إعادة العمل</th></tr></thead>
                    <tbody>
                        @foreach ($reviewers as $reviewer)
                            <tr>
                                <th>{{ $reviewer['name'] }}</th>
                                <td>{{ $reviewer['decisions'] }}</td>
                                <td><meter min="0" max="{{ $reviewerMaxMedian }}" value="{{ $reviewer['median_days'] ?? 0 }}"></meter>{{ $reviewer['median_days'] === null ? '—' : number_format($reviewer['median_days'], 1) }}</td>
                                <td>{{ number_format($reviewer['approval_share'], 1) }}%</td>
                                <td>{{ number_format($reviewer['rework_share'], 1) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-information.chart.figure>

    <x-information.chart.figure id="ports" title="الموانئ الأكثر تسجيلاً" icon="anchor">
        <x-information.chart.bar-list :items="$portCoverage" />
        <x-slot:table>
            <table class="info-chart-table"><thead><tr><th>الميناء</th><th>الطلبات</th></tr></thead><tbody>@foreach ($portCoverage as $item)<tr><th>{{ $item['label'] }}</th><td>{{ $item['value'] }}</td></tr>@endforeach</tbody></table>
        </x-slot:table>
    </x-information.chart.figure>
</section>

<x-information.section-head title="إجراءات مطلوبة" icon="bell" />

<section class="info-dashboard-grid info-dashboard-grid-attention">
    <x-information.chart.figure
        id="decision-queue" title="طلبات تنتظر قراراً" icon="clock"
        :subtitle="$decisionQueue['overdue'] > 0 ? $decisionQueue['overdue'].' منها تجاوزت العتبة' : null">
        <x-slot:action>
            <a class="info-chart-action" href="{{ $decisionQueue['href'] }}">عرض الكل ({{ $decisionQueue['total'] }})</a>
        </x-slot:action>

        @if (count($decisionQueue['items']) === 0)
            <p class="info-chart-empty">لا توجد طلبات مفتوحة ضمن الفترة.</p>
        @else
            <div class="info-chart-table-scroll">
                <table class="info-chart-table info-attention-table">
                    <thead><tr><th>الطلب</th><th>الميناء</th><th>الحالة</th><th>العمر</th></tr></thead>
                    <tbody>
                        @foreach ($decisionQueue['items'] as $item)
                            <tr @class(['is-alert' => $item['overdue']])>
                                <th><a href="{{ $item['href'] }}">{{ $item['reference'] }}</a></th>
                                <td>{{ $item['port'] }}</td>
                                <td><x-information.status-chip :status="$item['status']" /></td>
                                <td>{{ $item['age_days'] }} يوم</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-information.chart.figure>

    <x-information.chart.figure
        id="expiry" title="رخص تحتاج متابعة" icon="shield"
        :subtitle="$expiringLicenses['expired'] > 0 ? $expiringLicenses['expired'].' رخصة منتهية بالفعل' : null">
        <x-slot:action>
            <a class="info-chart-action" href="{{ $expiringLicenses['href'] }}">عرض الكل ({{ $expiringLicenses['total'] }})</a>
        </x-slot:action>

        @if (count($expiringLicenses['items']) === 0)
            <p class="info-chart-empty">لا توجد رخص قريبة من الانتهاء.</p>
        @else
            <div class="info-chart-table-scroll">
                <table class="info-chart-table info-attention-table">
                    <thead><tr><th>الطلب</th><th>الميناء</th><th>تاريخ الانتهاء</th><th>المتبقي</th></tr></thead>
                    <tbody>
                        @foreach ($expiringLicenses['items'] as $item)
                            <tr @class(['is-alert' => $item['expired']])>
                                <th><a href="{{ $item['href'] }}">{{ $item['reference'] }}</a></th>
                                <td>{{ $item['port'] }}</td>
                                <td>{{ $item['expiry'] }}</td>
                                <td>{{ $item['expired'] ? 'منتهية' : $item['days_left'].' يوم' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-information.chart.figure>

    <section class="info-chart-figure info-dashboard-activity is-wide" aria-labelledby="activity-title">
        <div class="info-chart-head">
            <div>
                <h2 id="activity-title">
                    <svg class="info-chart-head-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h3.5L9 5l4 14 2.5-7H21"></path></svg>
                    <span>النشاط الأخير</span>
                </h2>
            </div>
        </div>
        @if (count($recentActivity) === 0)
            <p class="info-chart-empty">لا يوجد نشاط ضمن الفترة المحددة.</p>
        @else
            <ol class="info-dashboard-activity-list">
                @foreach ($recentActivity as $activity)
                    <li>
                        <span class="info-dashboard-activity-marker" aria-hidden="true"></span>
                        <div>
                            <a href="{{ $activity['href'] }}">{{ $activity['reference'] }}</a>
                            <p>{{ $activity['actor'] }} · {{ \App\Models\InformationSubmission::STATUS_LABELS[$activity['from_status']] ?? 'بداية الطلب' }} ← {{ \App\Models\InformationSubmission::STATUS_LABELS[$activity['to_status']] ?? $activity['to_status'] }}</p>
                        </div>
                        <time datetime="{{ $activity['created_at']->toIso8601String() }}">{{ $activity['created_at']->diffForHumans() }}</time>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</section>

<div class="info-chart-tooltip" role="tooltip" hidden data-chart-tooltip></div>
@endsection
