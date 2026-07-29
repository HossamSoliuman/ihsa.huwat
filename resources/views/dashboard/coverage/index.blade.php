@extends('layouts.dashboard')

@section('title', 'التغطية الجغرافية')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/coverage.css') }}">
@endpush

@section('content')
<div class="coverage-console">
    <header class="coverage-head">
        <div><span>GEOGRAPHIC OPERATIONS</span><h1>التغطية الجغرافية</h1><p>صورة مباشرة لتوازن الموظفين والضغط التشغيلي عبر المناطق والمحافظات والموانئ.</p></div>
        @if(auth()->user()->role->code === 'super_admin' && $regions->count() > 1)
            <form method="get"><label>نطاق المنطقة<select name="region_id"><option value="">كل المناطق</option>@foreach($regions as $region)<option value="{{ $region->id }}" @selected((string) ($filters['region_id'] ?? '') === (string) $region->id)>{{ $region->name }}</option>@endforeach</select></label><button class="btn btn-primary" type="submit">تطبيق</button></form>
        @endif
    </header>

    <section class="coverage-kpis">
        <article><span>المناطق</span><strong>{{ $kpi['regions'] }}</strong></article><article><span>المحافظات</span><strong>{{ $kpi['governorates'] }}</strong></article><article><span>الموانئ</span><strong>{{ $kpi['ports'] }}</strong></article>
        <article class="is-covered"><span>مغطاة</span><strong>{{ $kpi['covered'] }}</strong></article><article class="is-uncovered"><span>غير مغطاة</span><strong>{{ $kpi['uncovered'] }}</strong></article><article class="is-load"><span>ضغط مرتفع</span><strong>{{ $kpi['high_load'] }}</strong></article>
        <article><span>موظفون متاحون</span><strong>{{ $kpi['available_employees'] }}</strong></article><article><span>تكليفات مؤقتة</span><strong>{{ $kpi['temp_assignments'] }}</strong></article>
    </section>

    <section class="coverage-map">
        <header><div><span>PORT STATUS MATRIX</span><h2>خريطة حالة الموانئ</h2></div><div class="coverage-legend"><i class="covered"></i>كافية <i class="load"></i>ضغط <i class="uncovered"></i>نقص <i class="inactive"></i>غير نشط</div></header>
        <div class="coverage-port-grid">
        @forelse($portRows as $row)
            <a href="{{ route('dashboard.coverage.index', array_filter(['region_id' => $filters['region_id'] ?? null, 'port_detail' => $row['port']->id])) }}" class="coverage-port status-{{ $row['status'] }} {{ ($portDetail['port']->id ?? null) === $row['port']->id ? 'is-selected' : '' }}">
                <i></i><small>{{ $row['port']->governorate->region->name }} / {{ $row['port']->governorate->name }}</small><h3>{{ $row['port']->name }}</h3>
                @if($row['status'] !== 'inactive')<dl><div><dt>الحاضرون</dt><dd>{{ $row['present_count'] }}</dd></div><div><dt>القوارب النشطة</dt><dd>{{ $row['active_trips'] }}</dd></div></dl>@else<p>الميناء غير نشط</p>@endif
            </a>
        @empty<div class="coverage-empty">لا توجد موانئ ضمن النطاق المحدد.</div>@endforelse
        </div>
    </section>

    @if($portDetail)
    <section class="coverage-detail">
        <header><div><span>PORT DRILLDOWN</span><h2>{{ $portDetail['port']->name }}</h2><p>{{ $portDetail['suggestion'] }}</p></div><span class="detail-status status-{{ $portDetail['status'] }}">{{ $portDetail['status'] }}</span></header>
        <div class="coverage-detail-grid">
            <div><h3>القوارب المتوقعة</h3><table><thead><tr><th>الرحلة</th><th>القارب</th><th>الوصول</th></tr></thead><tbody>@forelse($portDetail['expected_trips'] as $trip)<tr><td dir="ltr">{{ $trip->trip_code }}</td><td>{{ $trip->boat->name }}</td><td dir="ltr">{{ $trip->expected_arrival?->format('Y/m/d H:i') ?? '—' }}</td></tr>@empty<tr><td colspan="3">لا توجد قوارب متوقعة.</td></tr>@endforelse</tbody></table></div>
            <div><h3>فريق اليوم</h3><table><thead><tr><th>الموظف</th><th>المناوبة</th><th>الحالة</th></tr></thead><tbody>@forelse($portDetail['staff'] as $row)<tr><td>{{ $row['assignment']->employee->user->full_name }}</td><td>{{ config("attendance.shifts.{$row['assignment']->shift->name}") }}</td><td>{{ $row['attendance'] ? config("attendance.statuses.{$row['attendance']->status}") : 'لم يبدأ' }}</td></tr>@empty<tr><td colspan="3">لا يوجد موظفون مسندون اليوم.</td></tr>@endforelse</tbody></table></div>
        </div>
        <form method="post" action="{{ route('dashboard.coverage.assignments.store') }}" class="coverage-assign">@csrf<input type="hidden" name="date" value="{{ today()->toDateString() }}"><input type="hidden" name="port_id" value="{{ $portDetail['port']->id }}"><label>موظف متاح<select name="employee_id" required><option value="">اختر الموظف</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->user->full_name }}</option>@endforeach</select></label><label>المناوبة<select name="shift_id" required>@foreach($shifts as $shift)<option value="{{ $shift->id }}">{{ config("attendance.shifts.{$shift->name}") }}</option>@endforeach</select></label><button class="btn btn-primary" type="submit">تكليف اليوم</button></form>
    </section>
    @endif
</div>
@endsection
