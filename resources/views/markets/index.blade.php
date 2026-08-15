@extends('layouts.app')

@section('title', 'الأسواق')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'hammer'])</div>
            <div>
                <h1>الأسواق</h1>
                <p>أسواق ومزادات الأسماك: الكميات المعروضة والمباعة ومتوسطات الأسعار</p>
            </div>
        </div>
    </div>

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'الأسواق', 'value' => $stats['markets'], 'icon' => 'hammer', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'محلات البيع', 'value' => number_format($stats['shops']), 'icon' => 'building', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'دكات المزادات', 'value' => number_format($stats['stalls']), 'icon' => 'hammer', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'الكمية المعروضة', 'value' => number_format($stats['offered']), 'unit' => 'كجم', 'icon' => 'scale', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'الكمية المباعة', 'value' => number_format($stats['sold']), 'unit' => 'كجم', 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'متوسط السعر', 'value' => number_format($stats['avg_price'], 2), 'unit' => 'ريال/كجم', 'icon' => 'trending-up', 'tone' => 'primary'])
    </div>

    <div class="card" style="margin-bottom:1.25rem">
        <p class="card-title">متوسط سعر الكيلو حسب النوع</p>
        <div class="chart-wrap" style="height:300px;margin-top:.75rem"><canvas id="priceChart"></canvas></div>
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>المنطقة</span>
            <select class="select" name="region" onchange="this.form.submit()">
                <option value="">كل المناطق</option>
                @foreach ($regions as $region)<option value="{{ $region }}" @selected(request('region') === $region)>{{ $region }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>نوع السوق</span>
            <select class="select" name="type" onchange="this.form.submit()">
                <option value="">كل الأنواع</option>
                @foreach ($types as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>@endforeach
            </select>
        </label>
        <a href="{{ route('markets') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="cards-grid cols-3" style="margin-bottom:1.25rem">
        @forelse ($markets as $market)
            <div class="entity-card">
                <div style="display:flex;align-items:flex-start;justify-content:space-between">
                    <div>
                        <h3 style="font-weight:700">{{ $market->name }}</h3>
                        <p style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $market->governorate }} — {{ $market->region }}</p>
                    </div>
                    <span class="badge {{ $market->status === 'نشط' ? 'badge-ok' : 'badge-warn' }}">{{ $market->status }}</span>
                </div>
                <div class="mini-grid">
                    <div class="mini">@include('partials.icon', ['name' => 'hammer'])<div><p class="m-label">نوع السوق</p><p class="m-value">{{ $market->market_type }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'anchor'])<div><p class="m-label">الميناء</p><p class="m-value">{{ $market->port ?? '—' }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'building'])<div><p class="m-label">محلات البيع</p><p class="m-value">{{ $market->fish_shops_count }}</p></div></div>
                    <div class="mini">@include('partials.icon', ['name' => 'activity'])<div><p class="m-label">المزادات المسجلة</p><p class="m-value">{{ $market->auctions_count }}</p></div></div>
                </div>
            </div>
        @empty
            <div class="card" style="grid-column:1/-1;padding:2.5rem;text-align:center;font-size:.875rem;color:hsl(var(--muted-foreground))">لا توجد أسواق مطابقة</div>
        @endforelse
    </div>

    <p style="font-size:.82rem;font-weight:700;margin-bottom:.5rem">أحدث المزادات</p>
    <div class="table-card">
        <table class="data-table">
            <thead><tr><th>التاريخ</th><th>السوق</th><th>النوع</th><th>المعروض (كجم)</th><th>المباع (كجم)</th><th>نسبة البيع</th><th>متوسط السعر</th><th>المشتري</th></tr></thead>
            <tbody>
                @forelse ($auctions as $auction)
                    @php $pct = $auction->quantity_offered_kg ? round($auction->quantity_sold_kg / $auction->quantity_offered_kg * 100) : 0; @endphp
                    <tr>
                        <td style="white-space:nowrap">{{ $auction->auction_date?->toDateString() ?? '—' }}</td>
                        <td>{{ $auction->market?->name ?? '—' }}</td>
                        <td>{{ $auction->species?->name_ar ?? '—' }}</td>
                        <td>{{ number_format($auction->quantity_offered_kg) }}</td>
                        <td>{{ number_format($auction->quantity_sold_kg) }}</td>
                        <td><span class="badge {{ $pct >= 90 ? 'badge-ok' : ($pct >= 60 ? 'badge-warn' : 'badge-danger') }}">{{ $pct }}%</span></td>
                        <td style="font-weight:600">{{ number_format($auction->avg_price_per_kg, 2) }}</td>
                        <td>{{ $auction->buyer_type ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد مزادات مسجلة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = 'Tajawal';
    new Chart(document.getElementById('priceChart'), {
        type: 'bar',
        data: { labels: @json($priceBySpecies->keys()), datasets: [{ label: 'ريال/كجم', data: @json($priceBySpecies->values()), backgroundColor: '#0c4a6e', borderRadius: 4 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
</script>
@endpush