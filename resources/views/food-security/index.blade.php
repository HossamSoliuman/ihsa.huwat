@extends('layouts.app')

@section('title', 'الأمن الغذائي')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'globe'])</div>
            <div>
                <h1>الأمن الغذائي</h1>
            </div>
        </div>
    </div>

    @include('partials.section-head', ['icon' => 'gauge', 'title' => 'مؤشرات الإمداد الوطني'])

    <div class="stat-grid cols-6">
        @include('partials.stat-card', ['label' => 'الإنتاج الوطني', 'value' => number_format($totalTons), 'unit' => 'طن', 'icon' => 'fish', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'المصيد المعتمد', 'value' => number_format($approvedKg), 'unit' => 'كجم', 'icon' => 'badge-check', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'نصيب الفرد', 'value' => $perCapitaKg, 'unit' => 'كجم/سنة', 'icon' => 'users', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'عدد السكان', 'value' => number_format($population), 'icon' => 'globe', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'متوسط السعر', 'value' => number_format($avgPrice, 2), 'unit' => 'ريال/كجم', 'icon' => 'trending-up', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'القيمة التقديرية', 'value' => number_format($estimatedValue), 'unit' => 'ريال', 'icon' => 'scale', 'tone' => 'primary'])
    </div>

    @include('partials.section-head', ['icon' => 'map', 'title' => 'المساهمة والتغطية'])

    <div class="grid-2">
        <div class="card">
            <p class="card-title">مساهمة المناطق في الإمداد الوطني</p>
            <p class="card-sub" style="margin-bottom:.7rem">حصة كل منطقة من المصيد المعتمد</p>
            <div class="chart-wrap" style="min-height:290px"><canvas id="regionShare"></canvas></div>
        </div>
        <div class="card">
            <p class="card-title" style="margin-bottom:.7rem">مؤشر التغطية التقديري</p>
            <div class="gov-grid" style="grid-template-columns:repeat(2,1fr)">
                <div class="gov-box"><p class="g-label">نسبة التغطية</p><p class="g-value">{{ $selfSufficiency }}%</p></div>
                <div class="gov-box"><p class="g-label">نصيب الفرد شهريًا</p><p class="g-value">{{ round($perCapitaKg / 12, 2) }}</p></div>
            </div>
            <div class="progress" style="margin-top:.6rem"><div style="width:{{ $selfSufficiency }}%;background:{{ $selfSufficiency >= 70 ? '#0f7a5a' : ($selfSufficiency >= 40 ? '#b45309' : '#d61f47') }}"></div></div>
            <p style="margin-top:auto;padding-top:.75rem;font-size:.72rem;line-height:1.9;color:hsl(var(--muted-foreground))">تُحسب المؤشرات من المصيد المعتمد ومتوسطات أسعار المزادات، ولا تشمل الاستيراد أو الاستزراع السمكي.</p>
        </div>
    </div>

    @include('partials.section-head', ['icon' => 'clipboard', 'title' => 'التفصيل حسب المنطقة'])

    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>المنطقة</th><th>المصيد (طن)</th><th>الحصة من الإمداد</th><th>الموانئ</th><th>القوارب النشطة</th><th>الصيادون</th></tr></thead>
            <tbody>
                @foreach ($regions as $region)
                    @php $share = $totalTons ? round($region->total_catch_tons / $totalTons * 100, 1) : 0; @endphp
                    <tr>
                        <td style="font-weight:600">{{ $region->name }}</td>
                        <td>{{ number_format($region->total_catch_tons) }}</td>
                        <td><span class="badge badge-info">{{ $share }}%</span></td>
                        <td>{{ $region->ports_count }}</td>
                        <td>{{ number_format($region->active_boats) }}</td>
                        <td>{{ number_format($region->active_fishers) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
@include('partials.chart-setup')
<script>
    const regionLabels = @json($byRegion->keys());
    new Chart(document.getElementById('regionShare'), {
        type: 'doughnut',
        data: { labels: regionLabels, datasets: [{ data: @json($byRegion->values()), backgroundColor: hawatChart.colors(regionLabels.length) }] },
        options: { cutout: '58%', plugins: { legend: { position: 'bottom' } } }
    });
</script>
@endpush