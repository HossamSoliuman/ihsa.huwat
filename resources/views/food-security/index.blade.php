@extends('layouts.app')

@section('title', 'الأمن الغذائي')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'globe'])</div>
            <div>
                <h1>الأمن الغذائي</h1>
                <p>مؤشرات الإمداد السمكي الوطني ونصيب الفرد ومساهمة كل منطقة</p>
            </div>
        </div>
    </div>

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'الإنتاج الوطني', 'value' => number_format($totalTons), 'unit' => 'طن', 'icon' => 'fish', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'المصيد المعتمد', 'value' => number_format($approvedKg), 'unit' => 'كجم', 'icon' => 'badge-check', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'نصيب الفرد', 'value' => $perCapitaKg, 'unit' => 'كجم/سنة', 'icon' => 'users', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'عدد السكان', 'value' => number_format($population), 'icon' => 'globe', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'متوسط السعر', 'value' => number_format($avgPrice, 2), 'unit' => 'ريال/كجم', 'icon' => 'trending-up', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'القيمة التقديرية', 'value' => number_format($estimatedValue), 'unit' => 'ريال', 'icon' => 'scale', 'tone' => 'primary'])
    </div>

    <div class="grid-2" style="margin-bottom:1.25rem">
        <div class="card">
            <p class="card-title">مساهمة المناطق في الإمداد الوطني</p>
            <div class="chart-wrap" style="height:300px;margin-top:.75rem"><canvas id="regionShare"></canvas></div>
        </div>
        <div class="card">
            <p class="card-title" style="margin-bottom:.75rem">مؤشر التغطية التقديري</p>
            <div class="gov-grid" style="grid-template-columns:repeat(2,1fr)">
                <div class="gov-box"><p class="g-label">نسبة التغطية</p><p class="g-value">{{ $selfSufficiency }}%</p></div>
                <div class="gov-box"><p class="g-label">نصيب الفرد شهريًا</p><p class="g-value">{{ round($perCapitaKg / 12, 2) }}</p></div>
            </div>
            <div class="progress" style="margin-top:1rem"><div style="width:{{ $selfSufficiency }}%;background:{{ $selfSufficiency >= 70 ? '#10b981' : ($selfSufficiency >= 40 ? '#f59e0b' : '#f43f5e') }}"></div></div>
            <p style="margin-top:.75rem;font-size:.72rem;line-height:1.9;color:hsl(var(--muted-foreground))">تُحسب المؤشرات من المصيد المعتمد ومتوسطات أسعار المزادات، ولا تشمل الاستيراد أو الاستزراع السمكي.</p>
        </div>
    </div>

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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = 'Tajawal';
    new Chart(document.getElementById('regionShare'), {
        type: 'doughnut',
        data: { labels: @json($byRegion->keys()), datasets: [{ data: @json($byRegion->values()), backgroundColor: ['#0284c7', '#10b981', '#f59e0b', '#6366f1', '#f43f5e', '#0c4a6e', '#14b8a6'], borderWidth: 0 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
</script>
@endpush