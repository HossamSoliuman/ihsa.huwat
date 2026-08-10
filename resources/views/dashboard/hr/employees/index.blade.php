@extends('layouts.dashboard')

@section('title', 'الموظفون')

@section('content')
<div class="employment-admin-shell employee-directory">
    <header class="employee-page-head">
        <div><span class="employment-eyebrow">HR / EMPLOYEES</span><h1>الموظفون</h1></div>
        <div class="employee-head-actions">
            <a class="btn btn-outline" href="{{ route('dashboard.hr.employees.export', request()->query()) }}">تصدير CSV</a>
            @can('create', App\Models\Employee::class)
                <a class="btn btn-primary" href="{{ route('dashboard.hr.employees.create') }}">إضافة موظف</a>
            @endcan
        </div>
    </header>

    <section class="panel employee-directory-card">
        <form method="get" class="employee-directory-filters">
            <label class="is-search">بحث<input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="الاسم، الرقم الوظيفي، رقم الهوية"></label>
            <label>القسم<select name="department_id"><option value="">كل الأقسام</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $department->id)>{{ $department->name }}</option>@endforeach</select></label>
            <label>المسمى الوظيفي<select name="job_title_id"><option value="">كل المسميات</option>@foreach($jobTitles as $jobTitle)<option value="{{ $jobTitle->id }}" @selected((string) ($filters['job_title_id'] ?? '') === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>@endforeach</select></label>
            <label>الميناء<select name="port_id"><option value="">كل الموانئ</option>@foreach($ports as $port)<option value="{{ $port->id }}" @selected((string) ($filters['port_id'] ?? '') === (string) $port->id)>{{ $port->name }}</option>@endforeach</select></label>
            <label>نوع العقد<select name="contract_type"><option value="">كل العقود</option>@foreach(config('employment.contract_types') as $value => $label)<option value="{{ $value }}" @selected(($filters['contract_type'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>الحالة<select name="status"><option value="">كل الحالات</option>@foreach(config('employment.employee_statuses') as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>الجنسية<select name="nationality"><option value="">كل الجنسيات</option>@foreach($nationalities as $value => $label)<option value="{{ $value }}" @selected(($filters['nationality'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
            <div class="employee-filter-actions"><button class="btn btn-primary" type="submit">تطبيق</button><a class="btn btn-outline" href="{{ route('dashboard.hr.employees.index') }}">مسح</a></div>
        </form>

        <header class="employee-table-head"><h2>دليل الموظفين</h2><span>{{ $employees->total() }}</span></header>
        <div class="employment-table-wrap">
            <table class="employee-directory-table">
                <thead><tr><th>الموظف</th><th>الهوية</th><th>القسم</th><th>المسمى</th><th>الميناء</th><th>العقد</th><th>الحالة</th><th></th></tr></thead>
                <tbody>
                @forelse($employees as $employee)
                    <tr>
                        <td><a class="employee-name-link" href="{{ route('dashboard.hr.employees.show', $employee) }}"><strong>{{ $employee->user->full_name }}</strong><small dir="ltr">{{ $employee->employee_number }}</small></a></td>
                        <td dir="ltr">{{ $employee->national_id }}</td>
                        <td>{{ $employee->department?->name ?? '—' }}</td>
                        <td>{{ $employee->jobTitle?->name ?? '—' }}</td>
                        <td>{{ $employee->port?->name ?? '—' }}</td>
                        <td>{{ config("employment.contract_types.{$employee->activeContract?->contract_type}", '—') }}</td>
                        <td><span class="employee-state" data-state="{{ $employee->status }}">{{ config("employment.employee_statuses.{$employee->status}", $employee->status) }}</span></td>
                        <td><a class="employee-row-arrow" href="{{ route('dashboard.hr.employees.show', $employee) }}" aria-label="فتح ملف {{ $employee->user->full_name }}">←</a></td>
                    </tr>
                @empty
                    <tr><td class="employee-empty" colspan="8">لا توجد نتائج مطابقة.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $employees->links() }}
    </section>
</div>
@endsection
