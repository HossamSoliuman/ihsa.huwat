<x-information.section-head title="التوزيع الجغرافي" subtitle="المقارنة حسب {{ $geography['level'] }} عبر سجلات المركز" icon="chart" />

<section class="info-dashboard-grid info-dashboard-grid-geography">
    <x-information.chart.figure id="geo-submissions" title="الطلبات" icon="anchor">
        <x-information.chart.bar-list :items="$geography['submissions']" />
    </x-information.chart.figure>
    <x-information.chart.figure id="geo-markets" title="الأسواق" icon="layers">
        <x-information.chart.bar-list :items="$geography['markets']" />
    </x-information.chart.figure>
    <x-information.chart.figure id="geo-brokers" title="الدلالون" icon="users">
        <x-information.chart.bar-list :items="$geography['brokers']" />
    </x-information.chart.figure>
    <x-information.chart.figure id="geo-regions" title="حصة المناطق" icon="trend">
        <x-information.chart.stacked-bar :bars="$geography['regionShare']" />
    </x-information.chart.figure>
</section>
