@extends('layouts.dashboard')

@section('title', 'التقارير والتحليلات')
@section('body-class', 'reports-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/reports.css') }}">
@endpush

@section('content')
<div class="reports-shell">
    <header class="reports-hero"><div><span>ANALYTICS WORKBENCH</span><h1>التقارير والتحليلات</h1><p>استكشف البيانات التشغيلية والموارد البشرية والتغطية ضمن نطاق صلاحيتك، ثم صدّر النتيجة الحالية بصيغة CSV.</p></div><div class="report-period"><small>الفترة الحالية</small><strong dir="ltr">{{ $filters['date_from'] }} → {{ $filters['date_to'] }}</strong><span>{{ $rows->count() }} سجل</span></div></header>

    <nav class="report-types" aria-label="أنواع التقارير">@foreach($reportTypes as $key => $label)<a href="{{ route('dashboard.reports.index', array_merge(request()->except('page'), ['report_type' => $key])) }}" class="{{ $filters['report_type'] === $key ? 'active' : '' }}">{{ $label }}</a>@endforeach</nav>

    <form method="get" action="{{ route('dashboard.reports.index') }}" class="report-filters">
        <input type="hidden" name="report_type" value="{{ $filters['report_type'] }}">
        <div class="filter-primary"><label>من<input type="date" name="date_from" value="{{ $filters['date_from'] }}" required></label><label>إلى<input type="date" name="date_to" value="{{ $filters['date_to'] }}" required></label><label>المنطقة<select name="region_id"><option value="">كل المناطق</option>@foreach($regions as $item)<option value="{{ $item->id }}" @selected((string)($filters['region_id'] ?? '') === (string)$item->id)>{{ $item->name }}</option>@endforeach</select></label><label>المحافظة<select name="governorate_id"><option value="">كل المحافظات</option>@foreach($governorates as $item)<option value="{{ $item->id }}" @selected((string)($filters['governorate_id'] ?? '') === (string)$item->id)>{{ $item->name }}</option>@endforeach</select></label><label>الميناء<select name="port_id"><option value="">كل الموانئ</option>@foreach($ports as $item)<option value="{{ $item->id }}" @selected((string)($filters['port_id'] ?? '') === (string)$item->id)>{{ $item->name }}</option>@endforeach</select></label></div>
        <details><summary>فلاتر متقدمة</summary><div class="filter-advanced"><label>القارب<select name="boat_id"><option value="">الكل</option>@foreach($boats as $item)<option value="{{ $item->id }}" @selected((string)($filters['boat_id'] ?? '') === (string)$item->id)>{{ $item->name }}</option>@endforeach</select></label><label>الكابتن<select name="captain_id"><option value="">الكل</option>@foreach($captains as $item)<option value="{{ $item->id }}" @selected((string)($filters['captain_id'] ?? '') === (string)$item->id)>{{ $item->full_name }}</option>@endforeach</select></label><label>الموظف<select name="employee_id"><option value="">الكل</option>@foreach($employees as $item)<option value="{{ $item->id }}" @selected((string)($filters['employee_id'] ?? '') === (string)$item->id)>{{ $item->user->full_name }}</option>@endforeach</select></label><label>نوع السمك<select name="species_id"><option value="">الكل</option>@foreach($species as $item)<option value="{{ $item->id }}" @selected((string)($filters['species_id'] ?? '') === (string)$item->id)>{{ $item->name_ar }}</option>@endforeach</select></label><label>حالة الرحلة<select name="status"><option value="">كل الحالات</option>@foreach(config('reports.trip_statuses') as $key => $label)<option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></label><label>أدنى فرق %<input type="number" name="diff_min" min="0" max="100" step="0.1" value="{{ $filters['diff_min'] ?? '' }}"></label><label>أقصى فرق %<input type="number" name="diff_max" min="0" max="100" step="0.1" value="{{ $filters['diff_max'] ?? '' }}"></label></div></details>
        <div class="filter-actions"><button class="btn btn-primary" type="submit">تطبيق الفلاتر</button><a class="btn btn-outline" href="{{ route('dashboard.reports.index', ['report_type' => $filters['report_type']]) }}">إعادة ضبط</a><a class="btn btn-outline export-link" href="{{ route('dashboard.reports.export', request()->query()) }}">تصدير CSV</a></div>
    </form>

    <section class="report-result"><header><div><span>ACTIVE DATASET</span><h2>{{ $reportTitle }}</h2></div><div><strong>{{ $rows->count() }}</strong><small>صف ظاهر</small></div></header>@if($isLimited)<p class="limit-notice">تم عرض أول {{ config('reports.limit') }} صف. استخدم الفلاتر لتضييق النطاق.</p>@endif<div class="report-table"><table><thead><tr>@foreach($columns as $column)<th>{{ $column }}</th>@endforeach</tr></thead><tbody>@forelse($rows as $row)<tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($columns) }}">لا توجد بيانات مطابقة للفلاتر الحالية.</td></tr>@endforelse</tbody></table></div></section>
</div>
@endsection
