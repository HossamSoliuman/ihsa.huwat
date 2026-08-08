<x-information.section-head title="سجلّات المركز" subtitle="الحجم الحالي للسجلات — لا يتأثر بفلتر الفترة" icon="grid" />

<section class="info-dashboard-kpis info-dashboard-census" aria-label="سجلات مركز المعلومات">
    @foreach ($census as $tile)
        <x-information.chart.stat-tile
            :label="$tile['label']" :value="$tile['value']" :suffix="$tile['suffix']"
            :delta="$tile['delta']" :delta-good="$tile['delta_good']" :delta-suffix="$tile['delta_suffix']"
            :icon="$tile['icon']" :tone="$tile['tone']" :href="$tile['href']" />
    @endforeach
</section>
