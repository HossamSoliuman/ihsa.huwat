@extends('layouts.dashboard')

@section('title', 'الموارد البشرية')

@section('content')
<div class="employment-admin-shell">
    <header class="employee-page-head">
        <div><span class="employment-eyebrow">HR / OVERVIEW</span><h1>الموارد البشرية</h1></div>
        <div class="employee-head-actions"><a class="btn btn-outline" href="{{ route('dashboard.hr.settings.index') }}">الإعدادات</a><a class="btn btn-primary" href="{{ route('dashboard.hr.employees.index') }}">الموظفون</a></div>
    </header>

    <section class="employment-kpis" aria-label="مؤشرات الموارد البشرية">
        <article><span>إجمالي الموظفين</span><strong>{{ $kpi['total'] }}</strong></article>
        <article><span>النشطون</span><strong>{{ $kpi['active'] }}</strong></article>
        <article><span>عقود دائمة</span><strong>{{ $kpi['permanent'] }}</strong></article>
        <article><span>عقود مؤقتة</span><strong>{{ $kpi['temporary'] }}</strong></article>
        <article><span>عقود قاربت الانتهاء</span><strong>{{ $kpi['expiring'] }}</strong></article>
        <article><span>في إجازة</span><strong>{{ $kpi['on_leave'] }}</strong></article>
        <article><span>إجازات معلقة</span><strong>{{ $kpi['pending_leaves'] }}</strong></article>
        <article><span>جدد هذا الشهر</span><strong>{{ $kpi['new_this_month'] }}</strong></article>
    </section>

    <div class="grid-2">
        <section class="panel">
            <header class="employment-section-heading"><div><span>CONTRACT WATCH</span><h3>العقود القريبة من الانتهاء</h3></div></header>
            <div class="employment-table-wrap"><table><thead><tr><th>الموظف</th><th>رقم العقد</th><th>النهاية</th><th>المتبقي</th></tr></thead><tbody>
            @forelse($expiringContracts as $employee)
                <tr><td><a href="{{ route('dashboard.hr.employees.show', $employee) }}">{{ $employee->user->full_name }}</a></td><td dir="ltr">{{ $employee->activeContract->contract_number }}</td><td>{{ $employee->activeContract->end_date->format('Y-m-d') }}</td><td>{{ today()->diffInDays($employee->activeContract->end_date) }} يوم</td></tr>
            @empty
                <tr><td colspan="4">لا توجد عقود قريبة.</td></tr>
            @endforelse
            </tbody></table></div>
        </section>

        <section class="panel">
            <header class="employment-section-heading"><div><span>LEAVE QUEUE</span><h3>الإجازات المعلقة</h3></div></header>
            <div class="employment-table-wrap"><table><thead><tr><th>الموظف</th><th>البداية</th><th>النهاية</th></tr></thead><tbody>
            @forelse($pendingLeaves as $leave)
                <tr><td>{{ $leave->employee->user->full_name }}</td><td>{{ $leave->start_date->format('Y-m-d') }}</td><td>{{ $leave->end_date->format('Y-m-d') }}</td></tr>
            @empty
                <tr><td colspan="3">لا توجد إجازات معلقة.</td></tr>
            @endforelse
            </tbody></table></div>
        </section>
    </div>

    <section class="panel employment-list-panel">
        <header class="employment-section-heading"><div><span>DAILY PLACEMENT</span><h3>التوزيع الجغرافي اليوم</h3></div></header>
        <div class="employment-table-wrap"><table><thead><tr><th>المنطقة</th><th>المحافظة</th><th>الميناء</th><th>الموظفون</th></tr></thead><tbody>
        @forelse($byGeo as $row)
            <tr><td>{{ $row['port']->governorate->region->name }}</td><td>{{ $row['port']->governorate->name }}</td><td>{{ $row['port']->name }}</td><td>{{ $row['employees_count'] }}</td></tr>
        @empty
            <tr><td colspan="4">لا توجد تكليفات اليوم.</td></tr>
        @endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
