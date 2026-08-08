<x-information.section-head title="تحليل الأسطول والصيد" subtitle="تركيبة الطلبات المعتمدة ضمن الفترة المحددة" icon="chart" />

<section class="info-dashboard-grid info-dashboard-grid-fleet">
    <x-information.chart.figure id="fleet-boats" title="أنواع المراكب" icon="anchor">
        <x-information.chart.bar-list :items="$fleet['boatTypes']" />
    </x-information.chart.figure>
    <x-information.chart.figure id="fleet-methods" title="طرق الصيد" icon="layers">
        <x-information.chart.bar-list :items="$fleet['fishingMethods']" />
    </x-information.chart.figure>
    <x-information.chart.figure id="fleet-crew" title="حجم الطاقم" icon="users">
        <x-information.chart.bar-list :items="$fleet['crewBuckets']" />
    </x-information.chart.figure>
    <x-information.chart.figure id="fleet-nationalities" title="جنسية المالك" icon="pulse">
        <x-information.chart.bar-list :items="$fleet['ownerNationalities']" />
    </x-information.chart.figure>
</section>
