@extends('layouts.dashboard')

@section('title', 'إضافة موظف')

@section('content')
<div class="employment-admin-shell employee-editor-page">
    <header class="employee-page-head">
        <div><span class="employment-eyebrow">HR / NEW RECORD</span><h1>إضافة موظف</h1></div>
        <a class="btn btn-outline" href="{{ route('dashboard.hr.employees.index') }}">العودة</a>
    </header>

    <section class="panel employee-form-card">
        <form method="post" action="{{ route('dashboard.hr.employees.store') }}" class="employee-create-form">
            @csrf
            <label>الاسم<input name="full_name" value="{{ old('full_name') }}" maxlength="150" required></label>
            <label>رقم الهوية<input name="national_id" value="{{ old('national_id') }}" maxlength="20" dir="ltr" required></label>
            <label>الجنسية<select name="nationality" required><option value="">اختر الجنسية</option>@foreach($nationalities as $value => $label)<option value="{{ $value }}" @selected(old('nationality') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>تاريخ الميلاد<input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required></label>
            <label>الجنس<select name="gender" required><option value="">اختر الجنس</option><option value="male" @selected(old('gender') === 'male')>ذكر</option><option value="female" @selected(old('gender') === 'female')>أنثى</option></select></label>
            <label>الجوال<input name="phone" value="{{ old('phone') }}" maxlength="30" dir="ltr" required></label>
            <label>البريد الإلكتروني<input type="email" name="email" value="{{ old('email') }}" maxlength="190" dir="ltr" required></label>
            <label>القسم<select name="department_id" required><option value="">اختر القسم</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>@endforeach</select></label>
            <label>المسمى الوظيفي<select name="job_title_id" required><option value="">اختر المسمى</option>@foreach($jobTitles as $jobTitle)<option value="{{ $jobTitle->id }}" @selected((string) old('job_title_id') === (string) $jobTitle->id)>{{ $jobTitle->name }}</option>@endforeach</select></label>
            <label>المدير المباشر<select name="manager_id"><option value="">بدون مدير مباشر</option>@foreach($managers as $manager)<option value="{{ $manager->id }}" @selected((string) old('manager_id') === (string) $manager->id)>{{ $manager->user->full_name }}</option>@endforeach</select></label>
            <label>الميناء<select name="port_id"><option value="">بدون ميناء</option>@foreach($ports as $port)<option value="{{ $port->id }}" @selected((string) old('port_id') === (string) $port->id)>{{ $port->name }}</option>@endforeach</select></label>
            <label>تاريخ التعيين<input type="date" name="hire_date" value="{{ old('hire_date', today()->toDateString()) }}" required></label>
            <label>نوع العقد<select name="contract_type" required>@foreach(config('employment.contract_types') as $value => $label)<option value="{{ $value }}" @selected(old('contract_type', 'permanent') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>بداية العقد<input type="date" name="contract_start_date" value="{{ old('contract_start_date', today()->toDateString()) }}" required></label>
            <label>نهاية العقد<input type="date" name="contract_end_date" value="{{ old('contract_end_date') }}"></label>
            <label>نهاية التجربة<input type="date" name="probation_end_date" value="{{ old('probation_end_date') }}"></label>
            <label>ساعات العمل يوميًا<input type="number" name="working_hours_per_day" value="{{ old('working_hours_per_day', 8) }}" min="0.5" max="24" step="0.25" required></label>
            <label>أيام العمل أسبوعيًا<input type="number" name="working_days_per_week" value="{{ old('working_days_per_week', 6) }}" min="1" max="7" required></label>
            <label>الراتب الأساسي<input type="number" name="base_salary" value="{{ old('base_salary') }}" min="0.01" step="0.01" dir="ltr" required></label>
            <label>الدور<select name="role_id" required><option value="">اختر الدور</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>{{ $role->name_ar }}</option>@endforeach</select></label>
            <label>اسم المستخدم<input name="username" value="{{ old('username') }}" maxlength="100" dir="ltr" required></label>
            <label>كلمة المرور<input type="password" name="password" required></label>
            <label>تأكيد كلمة المرور<input type="password" name="password_confirmation" required></label>
            <div class="employee-form-actions"><button class="btn btn-primary" type="submit">حفظ الموظف</button><a class="btn btn-outline" href="{{ route('dashboard.hr.employees.index') }}">إلغاء</a></div>
        </form>
    </section>
</div>
@endsection
