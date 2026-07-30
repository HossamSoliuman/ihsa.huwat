@extends('government.layouts.app')

@section('title', 'إدارة المواسم')
@section('body-class', 'government-portal-page government-seasons-page')

@section('content')
<div class="government-shell">
    <header class="government-commandbar panel">
        <div>
            <span class="government-eyebrow">GOV // SEASONS REGISTRY</span>
            <h1>إدارة مواسم الصيد</h1>
            <p>ضبط فترات الصيد والرخص والأدوات والقيود التنظيمية لكل منطقة.</p>
        </div>
        <div class="government-command-actions">
            <button class="btn btn-outline" type="button" data-print>طباعة السجل</button>
            <a class="btn btn-primary" href="{{ route('government.seasons.create') }}">إضافة موسم</a>
        </div>
    </header>

    <section class="hud-stats government-season-kpis" aria-label="ملخص المواسم">
        @foreach([
            ['المواسم القادمة', (int) $summary->upcoming, 'فترات مجدولة ولم تبدأ'],
            ['المواسم النشطة', (int) $summary->active, 'مفتوحة للصيد حالياً'],
            ['الرخص الموسمية', (int) $summary->licenses, 'إجمالي الرخص المسجلة'],
            ['إجمالي المواسم', (int) $summary->total, 'كل السجلات التاريخية'],
        ] as [$label, $value, $meta])
            <article class="hud-stat-card">
                <div class="hud-card-title"><span>{{ $label }}</span></div>
                <div class="hud-stat-main"><strong class="hud-stat-value">{{ number_format($value) }}</strong><span class="government-signal" aria-hidden="true"><i></i><i></i><i></i><i></i></span></div>
                <div class="hud-stat-meta"><span><i class="meta-dot"></i>{{ $meta }}</span></div>
            </article>
        @endforeach
    </section>

    <section class="panel government-filter-panel">
        <div class="government-section-heading"><div><span>FILTER MATRIX</span><h2>تصفية السجل</h2></div><a href="{{ route('government.seasons.index') }}">إعادة التعيين</a></div>
        <form method="GET" action="{{ route('government.seasons.index') }}">
            <div class="government-filter-grid">
                <label class="government-field">من تاريخ<input name="from" type="date" value="{{ request('from') }}"></label>
                <label class="government-field">إلى تاريخ<input name="to" type="date" value="{{ request('to') }}"></label>
                <label class="government-field">الحالة<select name="status"><option value="">كل الحالات</option>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
                <label class="government-field">المنطقة<select name="region_id"><option value="">كل المناطق</option>@foreach($regions as $region)<option value="{{ $region->id }}" @selected((string) request('region_id') === (string) $region->id)>{{ $region->name }}</option>@endforeach</select></label>
                <label class="government-field government-search-field">بحث<input name="search" type="search" maxlength="120" value="{{ request('search') }}" placeholder="اسم الموسم"></label>
                <button class="btn btn-primary" type="submit">تطبيق التصفية</button>
            </div>
        </form>
    </section>

    <section class="panel government-register">
        <div class="government-section-heading"><div><span>SEASON LOG</span><h2>المواسم المسجلة</h2></div><small>{{ number_format($seasons->total()) }} نتيجة</small></div>
        <div class="table-responsive">
            <table class="government-table government-seasons-table">
                <thead><tr><th>#</th><th>اسم الموسم</th><th>الحالة</th><th>المنطقة</th><th>الفترة</th><th>الرخص</th><th>المقاس الأدنى</th><th>المقاس الأعلى</th><th>أدوات الصيد</th></tr></thead>
                <tbody>
                @forelse($seasons as $season)
                    <tr>
                        <td class="mono">{{ $season->id }}</td>
                        <td><strong>{{ $season->name }}</strong></td>
                        <td><span class="badge badge-{{ $season->status === 'active' ? 'success' : ($season->status === 'upcoming' ? 'warning' : 'muted') }}">{{ $statuses[$season->status] ?? $season->status }}</span></td>
                        <td>{{ $season->region->name }}</td>
                        <td class="mono" dir="ltr">{{ $season->start_date->format('Y/m/d') }} — {{ $season->end_date->format('Y/m/d') }}</td>
                        <td class="mono">{{ number_format($season->licenses_count) }}</td>
                        <td>{{ $season->minimum_size !== null ? $season->minimum_size.' سم' : '—' }}</td>
                        <td>{{ $season->maximum_size !== null ? $season->maximum_size.' سم' : '—' }}</td>
                        <td>{{ implode('، ', $season->fishing_tools) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9"><div class="government-table-empty"><strong>لا توجد مواسم مطابقة</strong><span>غيّر معايير التصفية أو أضف موسماً جديداً.</span></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($seasons->hasPages())
            <nav class="government-pagination" aria-label="صفحات المواسم">
                @if($seasons->onFirstPage())<span aria-disabled="true">السابق</span>@else<a href="{{ $seasons->previousPageUrl() }}" rel="prev">السابق</a>@endif
                @foreach($seasons->getUrlRange(max(1, $seasons->currentPage() - 2), min($seasons->lastPage(), $seasons->currentPage() + 2)) as $page => $url)
                    <a href="{{ $url }}" @class(['active' => $page === $seasons->currentPage()]) @if($page === $seasons->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                @endforeach
                @if($seasons->hasMorePages())<a href="{{ $seasons->nextPageUrl() }}" rel="next">التالي</a>@else<span aria-disabled="true">التالي</span>@endif
            </nav>
        @endif
    </section>
</div>
@endsection
