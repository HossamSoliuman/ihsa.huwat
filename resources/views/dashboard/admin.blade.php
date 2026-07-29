@extends('layouts.dashboard')

@section('title', 'الرئيسية - الإدارة العليا')
@section('body-class', 'admin-dashboard')

@section('content')
<section class="hud-stats" aria-label="ملخص مؤشرات الإدارة">
    @foreach([
        ['النطاق الجغرافي', $kpi['regions'], $kpi['governorates'].' محافظة و'.$kpi['ports'].' ميناء نشط'],
        ['موظفو الإحصاء', $kpi['employees'], $kpi['uncovered_ports'].' موانئ تحتاج تغطية'],
        ['حركة اليوم', $kpi['boats_today'], number_format($kpi['catch_today']).' كجم مصيد موثق'],
        ['جودة العمليات', $kpi['diff_trips'], 'رحلات بانتظار المراجعة'],
    ] as [$label, $value, $meta])
        <article class="hud-stat-card"><div class="hud-card-title"><span>{{ $label }}</span></div><div class="hud-stat-main"><span class="hud-stat-value">{{ number_format($value) }}</span></div><div class="hud-stat-meta"><span><i class="meta-dot"></i>{{ $meta }}</span></div></article>
    @endforeach
</section>
<section class="dashboard-analytics-grid">
    <article class="panel analytics-panel"><div class="panel-titlebar"><h2>إنتاج المناطق — آخر 30 يوماً</h2></div>@forelse($regionProduction as $region)<div class="species-row"><span>{{ $region->region_name }}</span><b>{{ number_format($region->total_kg) }} كجم</b></div>@empty<div class="analytics-empty">لا توجد بيانات إنتاج حتى الآن.</div>@endforelse</article>
    <article class="panel analytics-panel"><div class="panel-titlebar"><h2>أعلى أنواع المصيد</h2></div>@forelse($topSpecies as $species)<div class="species-row"><span>{{ $species->name_ar }}</span><b>{{ number_format($species->total_kg) }} كجم</b></div>@empty<div class="analytics-empty">لا توجد بيانات مصيد مصنفة بعد.</div>@endforelse</article>
</section>
<article class="panel dashboard-alerts"><div class="panel-titlebar"><h2>سجل تنبيهات الإدارة</h2></div>@if($alerts->isEmpty())<div class="alert-log-row is-clear">لا توجد تنبيهات غير محلولة حالياً.</div>@else<div class="table-responsive"><table><thead><tr><th>النوع</th><th>الرسالة</th><th>الخطورة</th><th>التاريخ</th></tr></thead><tbody>@foreach($alerts as $alert)<tr><td>{{ $alert->type }}</td><td>{{ $alert->message }}</td><td><span class="badge badge-{{ $alert->severity === 'critical' ? 'danger' : 'warning' }}">{{ $alert->severity }}</span></td><td>{{ $alert->created_at?->format('Y/m/d H:i') }}</td></tr>@endforeach</tbody></table></div>@endif</article>
@endsection
