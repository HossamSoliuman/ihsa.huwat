@extends('government.layouts.app')

@section('title', 'لوحة الحكومة')
@section('body-class', 'government-portal-page')

@section('content')
<div class="government-shell">
    <header class="government-commandbar panel">
        <div>
            <span class="government-eyebrow">GOV // CONTROL CENTER</span>
            <h1>لوحة التحكم الحكومية</h1>
            <p>نظرة موحدة على التشغيل البحري، الإنتاج، المواسم، والرخص المسجلة في نظام إحصاء المصيد.</p>
        </div>
        <div class="government-command-actions">
            <time datetime="{{ $generatedAt->toIso8601String() }}">
                <small>آخر قراءة</small>
                <strong>{{ $generatedAt->translatedFormat('l، j F Y · h:i A') }}</strong>
            </time>
            <a class="btn btn-primary" href="{{ route('government.seasons.index') }}">إدارة المواسم</a>
        </div>
    </header>

    @php
        $stats = [
            ['label' => 'رحلات الصيد', 'value' => number_format($kpi['trips']), 'meta' => 'إجمالي الرحلات المسجلة'],
            ['label' => 'الموظفون النشطون', 'value' => number_format($kpi['active_employees']), 'meta' => 'ضمن فرق التشغيل والإحصاء'],
            ['label' => 'الإنتاج السنوي', 'value' => number_format($kpi['yearly_production'], 1).' كجم', 'meta' => 'مصيد معتمد خلال العام'],
            ['label' => 'المواسم النشطة', 'value' => number_format($kpi['active_seasons']), 'meta' => 'مواسم صيد مفتوحة حالياً'],
            ['label' => 'الموانئ النشطة', 'value' => number_format($kpi['active_ports']), 'meta' => 'مرافق بحرية قيد التشغيل'],
            ['label' => 'الرخص الموسمية', 'value' => number_format($kpi['active_season_licenses']), 'meta' => 'ضمن المواسم النشطة'],
            ['label' => 'حركة اليوم', 'value' => number_format($kpi['today_trips']), 'meta' => 'رحلات وصلت اليوم'],
            ['label' => 'بانتظار المراجعة', 'value' => number_format($kpi['pending_reviews']), 'meta' => 'فروقات تشغيلية غير مغلقة'],
        ];
    @endphp

    <section class="hud-stats government-kpis" aria-label="المؤشرات الحكومية الرئيسية">
        @foreach($stats as $index => $stat)
            <article class="hud-stat-card">
                <div class="hud-card-title"><span>{{ $stat['label'] }}</span><small>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</small></div>
                <div class="hud-stat-main"><strong class="hud-stat-value">{{ $stat['value'] }}</strong><span class="government-signal" aria-hidden="true"><i></i><i></i><i></i><i></i></span></div>
                <div class="hud-stat-meta"><span><i class="meta-dot"></i>{{ $stat['meta'] }}</span></div>
            </article>
        @endforeach
    </section>

    <section class="dashboard-analytics-grid government-analytics">
        <article class="panel analytics-panel government-production-panel">
            <div class="panel-titlebar">
                <div><span class="government-panel-code">REGION / 30D</span><h2>إنتاج المناطق</h2></div>
                <small>{{ number_format((float) $regionProduction->sum('total_kg'), 1) }} كجم</small>
            </div>
            <div class="government-region-list">
                @forelse($regionProduction as $region)
                    <div class="government-region-row">
                        <span>{{ $region->region_name }}</span>
                        <meter class="government-meter" min="0" max="{{ max(1, $regionProduction->max('total_kg')) }}" value="{{ $region->total_kg }}">{{ $region->total_kg }}</meter>
                        <strong>{{ number_format($region->total_kg, 1) }} كجم</strong>
                    </div>
                @empty
                    <div class="government-empty"><span>00</span><div><strong>لا توجد بيانات إنتاج بعد</strong><p>ستظهر قراءة المناطق عند اعتماد أولى رحلات الصيد.</p></div></div>
                @endforelse
            </div>
        </article>

        <article class="panel analytics-panel government-seasons-panel">
            <div class="panel-titlebar">
                <div><span class="government-panel-code">SEASON / LATEST</span><h2>أحدث المواسم</h2></div>
                <a href="{{ route('government.seasons.index') }}">عرض السجل</a>
            </div>
            <div class="table-responsive">
                <table class="government-table">
                    <thead><tr><th>الموسم</th><th>المنطقة</th><th>الحالة</th><th>النهاية</th></tr></thead>
                    <tbody>
                    @forelse($recentSeasons as $season)
                        <tr>
                            <td><strong>{{ $season->name }}</strong></td>
                            <td>{{ $season->region->name }}</td>
                            <td><span class="badge badge-{{ $season->status === 'active' ? 'success' : ($season->status === 'upcoming' ? 'warning' : 'muted') }}">{{ config("government.season_statuses.{$season->status}") }}</span></td>
                            <td dir="ltr">{{ $season->end_date->format('Y/m/d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="government-table-empty">لا توجد مواسم مسجلة. ابدأ بإنشاء أول موسم صيد.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</div>
@endsection
