@extends('layouts.dashboard')

@section('title', 'لوحة المنطقة')
@section('body-class', 'jurisdiction-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/jurisdiction.css') }}">
@endpush

@section('content')
<div class="jurisdiction-shell region-shell">
    <header class="jurisdiction-hero">
        <div><span>REGIONAL COMMAND</span><h1>لوحة قيادة المنطقة</h1><p>مقارنة الأداء والتغطية والإنتاج عبر المحافظات والموانئ ضمن نطاق جغرافي واحد.</p></div>
        @if(auth()->user()->role->code === 'super_admin' && $regions->count() > 1)
            <form method="get" class="scope-filter"><label>المنطقة<select name="region_id">@foreach($regions as $item)<option value="{{ $item->id }}" @selected($region?->id === $item->id)>{{ $item->name }}</option>@endforeach</select></label><button class="btn btn-primary" type="submit">عرض</button></form>
        @endif
    </header>

    @if(!$region)
        <section class="jurisdiction-empty">لا توجد مناطق مسجلة ضمن نطاقك.</section>
    @else
        <section class="jurisdiction-context"><span>النطاق الحالي</span><strong>{{ $region->name }}</strong><small>{{ now()->format('Y/m/d H:i') }}</small></section>
        <section class="jurisdiction-kpis">
            @foreach([['المحافظات','governorates'],['الموانئ','ports'],['الموظفون النشطون','active_employees'],['الغائبون','absent_employees'],['رحلات اليوم','trips_today'],['الصيد المعتمد كجم','approved_catch'],['رحلات بفروقات','diff_trips'],['تحتاج دعماً','needs_support']] as [$label,$key])
                <article><span>{{ $label }}</span><strong>{{ number_format($kpi[$key] ?? 0, $key === 'approved_catch' ? 1 : 0) }}</strong></article>
            @endforeach
        </section>

        <section class="jurisdiction-grid two-one">
            <article class="command-panel wide"><header><span>30-DAY OUTPUT</span><h2>مقارنة المحافظات</h2></header><div class="table-scroll"><table><thead><tr><th>المحافظة</th><th>الموانئ</th><th>رحلات 30 يوماً</th><th>الإنتاج كجم</th><th>رحلات اليوم</th><th>متوسط الفرق</th><th>موانئ مغطاة</th></tr></thead><tbody>@forelse($governorateRows as $row)<tr><td>{{ $row['governorate']->name }}</td><td>{{ $row['ports'] }}</td><td>{{ $row['trips_30_days'] }}</td><td>{{ number_format($row['weight_30_days'], 1) }}</td><td>{{ $row['trips_today'] }}</td><td>{{ number_format($row['average_difference'], 1) }}%</td><td>{{ $row['covered'] }}/{{ $row['ports'] }}</td></tr>@empty<tr><td colspan="7">لا توجد محافظات ضمن المنطقة.</td></tr>@endforelse</tbody></table></div></article>
            <article class="command-panel"><header><span>LIVE LOAD</span><h2>أكثر الموانئ ازدحاماً</h2></header><ol class="rank-list">@forelse($busiestPorts as $row)<li><div><strong>{{ $row['port']->name }}</strong><small>{{ $row['port']->governorate->name }}</small></div><b>{{ $row['active_trips'] }}</b></li>@empty<li class="muted-row">لا توجد رحلات نشطة.</li>@endforelse</ol></article>
        </section>

        <section class="command-panel"><header><span>PORT COVERAGE</span><h2>حالة الموانئ والتغطية الحالية</h2></header><div class="status-grid">@forelse($portRows as $row)<article class="port-status status-{{ $row['status'] }}"><i></i><small>{{ $row['port']->governorate->name }}</small><h3>{{ $row['port']->name }}</h3><dl><div><dt>الحاضرون</dt><dd>{{ $row['present'] }}</dd></div><div><dt>الرحلات النشطة</dt><dd>{{ $row['active_trips'] }}</dd></div></dl></article>@empty<div class="jurisdiction-empty">لا توجد موانئ.</div>@endforelse</div></section>

        <section class="jurisdiction-grid three">
            <article class="command-panel"><header><span>STAFF MAP</span><h2>توزيع الموظفين</h2></header><ol class="rank-list">@foreach($staffDistribution as $row)<li><strong>{{ $row['port']->name }}</strong><b>{{ $row['employees'] }}</b></li>@endforeach</ol></article>
            <article class="command-panel"><header><span>TOP CREW</span><h2>الأعلى أداءً خلال 30 يوماً</h2></header><ol class="rank-list">@forelse($topEmployees as $row)<li><div><strong>{{ $row['employee']->user->full_name }}</strong><small>{{ number_format($row['weight'], 1) }} كجم</small></div><b>{{ $row['trips'] }}</b></li>@empty<li class="muted-row">لا توجد بيانات مكتملة.</li>@endforelse</ol></article>
            <article class="command-panel alert-panel"><header><span>SUPPORT SIGNALS</span><h2>تنبيهات نقص التغطية</h2></header><ul class="signal-list">@forelse($portRows->whereIn('status', ['uncovered','high_load']) as $row)<li class="signal-{{ $row['status'] }}"><strong>{{ $row['port']->name }}</strong><span>{{ $row['status'] === 'uncovered' ? 'لا يوجد موظف حاضر اليوم' : 'الضغط التشغيلي يتجاوز التغطية الحالية' }}</span></li>@empty<li class="all-clear">كل الموانئ ضمن مستوى التغطية الآمن.</li>@endforelse</ul></article>
        </section>
    @endif
</div>
@endsection
