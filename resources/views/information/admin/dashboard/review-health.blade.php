<x-information.section-head title="حالة المراجعة" subtitle="مؤشرات سير الطلبات ضمن الفترة المحددة" icon="chart" />

<section class="info-dashboard-kpis" aria-label="مؤشرات المراجعة">
    <x-information.chart.stat-tile
        :label="$hero['label']" :value="$hero['value']" :suffix="$hero['suffix']"
        :delta="$hero['delta']" :delta-good="$hero['delta_good']" :delta-suffix="$hero['delta_suffix']"
        :icon="$hero['icon']" :tone="$hero['tone']" :href="$hero['href']" />
    @foreach ($workflowKpis as $kpi)
        <x-information.chart.stat-tile
            :label="$kpi['label']" :value="$kpi['value']" :suffix="$kpi['suffix']"
            :delta="$kpi['delta']" :delta-good="$kpi['delta_good']" :delta-suffix="$kpi['delta_suffix']"
            :icon="$kpi['icon']" :tone="$kpi['tone']" :href="$kpi['href']" />
    @endforeach
</section>

<section class="info-dashboard-grid info-dashboard-grid-analytics">
    <x-information.chart.figure id="flow-trend" title="الطلبات عبر الزمن" icon="trend" :legend="$trend['series']">
        <x-information.chart.line :series="$trend['series']" />
        <x-slot:table>
            <table class="info-chart-table"><thead><tr><th>الفترة</th><th>الوارد</th><th>المنجز</th></tr></thead><tbody>
                @foreach ($trend['series'][0]['values'] as $index => $point)
                    <tr><th>{{ $point['label'] }}</th><td>{{ $point['value'] }}</td><td>{{ $trend['series'][1]['values'][$index]['value'] }}</td></tr>
                @endforeach
            </tbody></table>
        </x-slot:table>
    </x-information.chart.figure>

    <x-information.chart.figure id="pipeline" title="حالة الطلبات" icon="layers" :legend="$pipeline['segments']">
        <x-information.chart.stacked-bar :bars="[$pipeline]" />
    </x-information.chart.figure>

    <x-information.chart.figure id="reviewers" title="أداء المراجعين" icon="users" class="is-wide">
        @if (count($reviewers) === 0)
            <p class="info-chart-empty">لا توجد قرارات منسوبة إلى مراجعين ضمن الفترة.</p>
        @else
            @php $reviewerMaxMedian = max(1, (float) collect($reviewers)->max('median_days')); @endphp
            <div class="info-chart-table-scroll">
                <table class="info-chart-table info-reviewer-table"><thead><tr><th>المراجع</th><th>القرارات</th><th>متوسط الأيام</th><th>الاعتماد</th><th>إعادة العمل</th></tr></thead><tbody>
                    @foreach ($reviewers as $reviewer)
                        <tr><th>{{ $reviewer['name'] }}</th><td>{{ $reviewer['decisions'] }}</td><td><meter min="0" max="{{ $reviewerMaxMedian }}" value="{{ $reviewer['median_days'] ?? 0 }}"></meter>{{ $reviewer['median_days'] === null ? '—' : number_format($reviewer['median_days'], 1) }}</td><td>{{ number_format($reviewer['approval_share'], 1) }}%</td><td>{{ number_format($reviewer['rework_share'], 1) }}%</td></tr>
                    @endforeach
                </tbody></table>
            </div>
        @endif
    </x-information.chart.figure>
</section>
