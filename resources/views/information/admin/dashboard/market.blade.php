<x-information.section-head title="الأسواق والدلالين" subtitle="قدرة الأسواق، العمالة، ومزيج الدلالين" icon="chart" />

<section class="info-dashboard-grid info-dashboard-grid-markets">
    <x-information.chart.figure id="market-units" title="الوحدات لكل سوق" icon="layers">
        <x-information.chart.bar-list :items="$marketAnalysis['unitsPerMarket']" />
    </x-information.chart.figure>
    <x-information.chart.figure id="market-workers" title="العمالة حسب نوع الوحدة" icon="users">
        <x-information.chart.bar-list :items="$marketAnalysis['workersByUnitType']" />
    </x-information.chart.figure>
    <x-information.chart.figure id="market-brokers" title="مزيج الدلالين" icon="users">
        <x-information.chart.bar-list :items="$marketAnalysis['brokerMix']" />
    </x-information.chart.figure>
    <x-information.chart.figure id="market-coverage" title="فجوات تغطية الأسواق" icon="pulse">
        <div class="info-dashboard-gap-metrics">
            <a href="{{ $marketAnalysis['coverage']['href'] }}"><strong>{{ $marketAnalysis['coverage']['without_units'] }}</strong><span>أسواق بلا وحدات</span></a>
            <a href="{{ $marketAnalysis['coverage']['href'] }}"><strong>{{ $marketAnalysis['coverage']['without_brokers'] }}</strong><span>أسواق بلا دلالين</span></a>
        </div>
    </x-information.chart.figure>
</section>
