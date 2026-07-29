@extends('layouts.dashboard')

@section('title', 'الحضور والمناوبات')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/attendance.css') }}">
@endpush

@section('content')
<div class="attendance-board">
    <header class="attendance-head">
        <div><span>WORKFORCE / DAILY ROSTER</span><h1>الحضور والمناوبات</h1><p>لوحة تشغيل يومية لتسجيل الحركة، إدارة البدلاء، ومراقبة تغطية كل ميناء ومناوبة.</p></div>
        <form method="get" class="attendance-filter">
            <label>تاريخ التشغيل<input type="date" name="date" value="{{ $filters['date'] }}"></label>
            @if(auth()->user()->role->code !== 'port_supervisor')
                <label>الميناء<select name="port_id"><option value="">كل الموانئ</option>@foreach($ports as $port)<option value="{{ $port->id }}" @selected((string) ($filters['port_id'] ?? '') === (string) $port->id)>{{ $port->name }}</option>@endforeach</select></label>
            @endif
            <button class="btn btn-primary" type="submit">تحديث الوردية</button>
        </form>
    </header>

    <section class="attendance-kpis">
        <article class="tone-present"><span>الحاضرون</span><strong>{{ $kpi['present'] }}</strong></article>
        <article class="tone-absent"><span>الغائبون</span><strong>{{ $kpi['absent'] }}</strong></article>
        <article class="tone-late"><span>المتأخرون</span><strong>{{ $kpi['late'] }}</strong></article>
        <article><span>في إجازة</span><strong>{{ $kpi['on_leave'] }}</strong></article>
        <article><span>الصباحية</span><strong>{{ $kpi['morning'] }}</strong></article>
        <article><span>المسائية</span><strong>{{ $kpi['evening'] }}</strong></article>
        <article><span>الليلية</span><strong>{{ $kpi['night'] }}</strong></article>
        <article><span>عمل إضافي</span><strong>{{ $kpi['overtime_hours'] }}<small>س</small></strong></article>
    </section>

    <section class="attendance-roster">
        <header><div><span>DAILY MANIFEST</span><h2>كشف المناوبات اليومية</h2></div><b>{{ $rows->count() }} تكليف</b></header>
        <div class="attendance-table-wrap"><table class="attendance-table">
            <thead><tr><th>الموظف</th><th>الميناء / المناوبة</th><th>الحالة</th><th>الحضور</th><th>الانصراف</th><th>الإجراء</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                @php($assignment = $row['assignment']) @php($attendance = $row['attendance'])
                <tr>
                    <td><strong>{{ $assignment->employee->user->full_name }}</strong>@if($assignment->is_temporary)<small class="substitute-mark">موظف بديل</small>@endif</td>
                    <td><strong>{{ $assignment->port->name }}</strong><small>{{ config("attendance.shifts.{$assignment->shift->name}") }} · {{ $assignment->shift->start_time }}—{{ $assignment->shift->end_time }}</small></td>
                    <td><span class="attendance-state state-{{ $attendance?->status ?? 'pending' }}">{{ $attendance ? config("attendance.statuses.{$attendance->status}") : 'لم يبدأ' }}</span></td>
                    <td dir="ltr">{{ $attendance?->check_in?->format('H:i') ?? '—' }}</td>
                    <td dir="ltr">{{ $attendance?->check_out?->format('H:i') ?? '—' }}</td>
                    <td><div class="attendance-actions">
                        @if(!$attendance?->check_in)
                            <form method="post" action="{{ route('dashboard.attendance.check-in', $assignment) }}">@csrf<button type="submit">حضور</button></form>
                            <form method="post" action="{{ route('dashboard.attendance.absence', $assignment) }}">@csrf<button class="is-danger" type="submit">غياب</button></form>
                        @elseif(!$attendance->check_out)
                            <form method="post" action="{{ route('dashboard.attendance.check-out', $assignment) }}">@csrf<button type="submit">انصراف</button></form>
                        @endif
                        @if(!$attendance)
                            <form method="post" action="{{ route('dashboard.attendance.shift.update', $assignment) }}" class="shift-swap">@csrf @method('PATCH')<select name="shift_id">@foreach($shifts as $shift)<option value="{{ $shift->id }}" @selected($assignment->shift_id === $shift->id)>{{ config("attendance.shifts.{$shift->name}") }}</option>@endforeach</select><button type="submit">تبديل</button></form>
                        @endif
                    </div></td>
                </tr>
            @empty<tr><td colspan="6" class="attendance-empty">لا توجد تكليفات بهذا التاريخ ضمن النطاق المحدد.</td></tr>@endforelse
            </tbody>
        </table></div>
    </section>

    <div class="attendance-grid">
        <section class="attendance-roster coverage-panel">
            <header><div><span>COVERAGE GAPS</span><h2>فجوات التغطية</h2></div><b>{{ $coverageGaps->count() }}</b></header>
            <div class="coverage-list">
            @forelse($coverageGaps as $gap)
                <article><div><strong>{{ $gap['port']->name }}</strong><small>{{ config("attendance.shifts.{$gap['shift']->name}") }}</small></div>
                    <form method="post" action="{{ route('dashboard.attendance.substitutes.store') }}">@csrf<input type="hidden" name="date" value="{{ $filters['date'] }}"><input type="hidden" name="port_id" value="{{ $gap['port']->id }}"><input type="hidden" name="shift_id" value="{{ $gap['shift']->id }}"><select name="employee_id" required><option value="">اختر بديلاً</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->user->full_name }}</option>@endforeach</select><button class="btn btn-primary btn-sm" type="submit">تعيين</button></form>
                </article>
            @empty<div class="attendance-clear">✓ لا توجد فجوات تغطية في النطاق المحدد.</div>@endforelse
            </div>
        </section>

        <section class="attendance-roster compact-panel">
            <header><div><span>EXCEPTIONS</span><h2>البدلاء والتأخير</h2></div></header>
            <div class="exception-columns">
                <div><h3>الموظفون البدلاء</h3>@forelse($substituteRows as $row)<p><strong>{{ $row['assignment']->employee->user->full_name }}</strong><span>{{ $row['assignment']->port->name }} · {{ config("attendance.shifts.{$row['assignment']->shift->name}") }}</span></p>@empty<small>لا يوجد بدلاء بهذا التاريخ.</small>@endforelse</div>
                <div><h3>حالات التأخير</h3>@forelse($lateRows as $row)<p><strong>{{ $row['assignment']->employee->user->full_name }}</strong><span dir="ltr">{{ $row['attendance']->check_in->format('H:i') }}</span></p>@empty<small>لا توجد حالات تأخير.</small>@endforelse</div>
            </div>
        </section>
    </div>
</div>
@endsection
