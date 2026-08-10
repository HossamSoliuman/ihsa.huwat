@extends('layouts.dashboard')

@section('title', $employee->user->full_name)

@section('content')
<div class="employment-admin-shell employee-profile-page">
    <header class="employee-profile-head">
        <div class="employee-profile-mark">{{ mb_substr($employee->user->full_name, 0, 1) }}</div>
        <div class="employee-profile-title"><span dir="ltr">{{ $employee->employee_number }}</span><h1>{{ $employee->user->full_name }}</h1><p>{{ $employee->jobTitle?->name ?? '—' }} · {{ $employee->department?->name ?? '—' }}</p></div>
        <span class="employee-state" data-state="{{ $employee->status }}">{{ config("employment.employee_statuses.{$employee->status}", $employee->status) }}</span>
        <a class="btn btn-outline" href="{{ route('dashboard.hr.employees.index') }}">دليل الموظفين</a>
    </header>

    <nav class="employee-profile-tabs" aria-label="أقسام ملف الموظف">
        <a class="is-active" href="#overview">نظرة عامة</a><a href="#contracts">العقود</a>@if($canViewSalary)<a href="#salary">الراتب</a><a href="#payroll-history">المسيرات</a><a href="#loans">السلف</a><a href="#adjustments">التعديلات</a><a href="#bank">البنك</a><a href="#documents">المستندات</a>@endif<a href="#audit">السجل</a>
    </nav>

    <section id="overview" class="employee-profile-grid">
        <article class="panel employee-profile-card">
            <header><h2>البيانات الوظيفية</h2></header>
            <dl><div><dt>الرقم الوظيفي</dt><dd dir="ltr">{{ $employee->employee_number }}</dd></div><div><dt>تاريخ التعيين</dt><dd>{{ $employee->hire_date?->format('Y-m-d') }}</dd></div><div><dt>القسم</dt><dd>{{ $employee->department?->name ?? '—' }}</dd></div><div><dt>المسمى الوظيفي</dt><dd>{{ $employee->jobTitle?->name ?? '—' }}</dd></div><div><dt>المدير المباشر</dt><dd>{{ $employee->manager?->user?->full_name ?? '—' }}</dd></div><div><dt>الميناء</dt><dd>{{ $employee->port?->name ?? '—' }}</dd></div></dl>
        </article>
        <article class="panel employee-profile-card">
            <header><h2>بيانات الهوية والتواصل</h2></header>
            <dl><div><dt>رقم الهوية</dt><dd dir="ltr">{{ $employee->national_id }}</dd></div><div><dt>الجنسية</dt><dd>{{ $nationalityLabel ?? '—' }}</dd></div><div><dt>تاريخ الميلاد</dt><dd>{{ $employee->date_of_birth?->format('Y-m-d') ?? '—' }}</dd></div><div><dt>الجنس</dt><dd>{{ $employee->gender === 'male' ? 'ذكر' : ($employee->gender === 'female' ? 'أنثى' : '—') }}</dd></div><div><dt>الجوال</dt><dd dir="ltr">{{ $employee->phone ?? '—' }}</dd></div><div><dt>البريد الإلكتروني</dt><dd dir="ltr">{{ $employee->email ?? '—' }}</dd></div></dl>
        </article>
    </section>

    @can('update', $employee)
        <section class="panel employee-contract-form-card">
            <header><h2>تحديث بيانات الموظف</h2></header>
            <form method="post" action="{{ route('dashboard.hr.employees.update', $employee) }}" class="employee-contract-form">
                @csrf @method('PATCH')
                <label>الاسم الكامل<input name="full_name" value="{{ old('full_name',$employee->user->full_name) }}" maxlength="150" required></label>
                <label>رقم الهوية<input name="national_id" value="{{ old('national_id',$employee->national_id) }}" maxlength="20" dir="ltr" required></label>
                <label>الجنسية<select name="nationality" required>@foreach(App\Models\Nationality::options() as $value => $label)<option value="{{ $value }}" @selected(old('nationality',$employee->nationality) === $value)>{{ $label }}</option>@endforeach</select></label>
                <label>تاريخ الميلاد<input type="date" name="date_of_birth" value="{{ old('date_of_birth',$employee->date_of_birth?->toDateString()) }}" required></label>
                <label>الجنس<select name="gender" required><option value="male" @selected(old('gender',$employee->gender) === 'male')>ذكر</option><option value="female" @selected(old('gender',$employee->gender) === 'female')>أنثى</option></select></label>
                <label>الجوال<input name="phone" value="{{ old('phone',$employee->phone) }}" maxlength="30" dir="ltr" required></label>
                <label>البريد الإلكتروني<input type="email" name="email" value="{{ old('email',$employee->email) }}" maxlength="190" dir="ltr" required></label>
                <label>القسم<select name="department_id" required>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((int)old('department_id',$employee->department_id) === $department->id)>{{ $department->name }}</option>@endforeach</select></label>
                <label>المسمى الوظيفي<select name="job_title_id" required>@foreach($jobTitles as $jobTitle)<option value="{{ $jobTitle->id }}" @selected((int)old('job_title_id',$employee->job_title_id) === $jobTitle->id)>{{ $jobTitle->name }}</option>@endforeach</select></label>
                <label>المدير المباشر<select name="manager_id"><option value="">بدون مدير</option>@foreach($managers as $manager)<option value="{{ $manager->id }}" @selected((int)old('manager_id',$employee->manager_id) === $manager->id)>{{ $manager->user->full_name }}</option>@endforeach</select></label>
                <label>الميناء<select name="port_id"><option value="">بدون ميناء</option>@foreach($ports as $port)<option value="{{ $port->id }}" @selected((int)old('port_id',$employee->port_id) === $port->id)>{{ $port->name }}</option>@endforeach</select></label>
                <label>الحالة<select name="status" required>@foreach(config('employment.employee_statuses') as $value => $label) @if(in_array($value,['active','on_leave','suspended','terminated','inactive'],true))<option value="{{ $value }}" @selected(old('status',$employee->status) === $value)>{{ $label }}</option>@endif @endforeach</select></label>
                <label>تاريخ إنهاء الخدمة<input type="date" name="termination_date" value="{{ old('termination_date',$employee->termination_date?->toDateString()) }}"></label>
                <label class="is-wide">سبب إنهاء الخدمة<textarea name="termination_reason" maxlength="2000">{{ old('termination_reason',$employee->termination_reason) }}</textarea></label>
                <div class="employee-form-actions"><button class="btn btn-primary" type="submit">حفظ بيانات الموظف</button></div>
            </form>
        </section>
    @endcan

    <section id="contracts" class="panel employee-contract-card">
        <header><h2>العقود</h2><span>{{ $employee->contracts->count() }}</span></header>
        <div class="employment-table-wrap"><table><thead><tr><th>رقم العقد</th><th>النوع</th><th>البداية</th><th>النهاية</th><th>ساعات اليوم</th><th>أيام الأسبوع</th><th>الحالة</th></tr></thead><tbody>
        @forelse($employee->contracts as $contract)
            <tr><td dir="ltr">{{ $contract->contract_number }}</td><td>{{ config("employment.contract_types.{$contract->contract_type}", $contract->contract_type) }}</td><td>{{ $contract->start_date->format('Y-m-d') }}</td><td>{{ $contract->end_date?->format('Y-m-d') ?? '—' }}</td><td>{{ $contract->working_hours_per_day + 0 }}</td><td>{{ $contract->working_days_per_week }}</td><td><span class="employee-state" data-state="{{ $contract->status }}">{{ config("employment.contract_statuses.{$contract->status}", $contract->status) }}</span></td></tr>
        @empty
            <tr><td class="employee-empty" colspan="7">لا توجد عقود.</td></tr>
        @endforelse
        </tbody></table></div>
    </section>

    @can('create', [App\Models\EmployeeContract::class, $employee])
        <section class="panel employee-contract-form-card">
            <header><h2>{{ $employee->activeContract ? 'تجديد العقد' : 'إضافة عقد' }}</h2></header>
            <form method="post" action="{{ $employee->activeContract ? route('dashboard.hr.employees.contracts.renew', $employee) : route('dashboard.hr.employees.contracts.store', $employee) }}" class="employee-contract-form">
                @csrf
                <label>نوع العقد<select name="contract_type" required>@foreach(config('employment.contract_types') as $value => $label)<option value="{{ $value }}" @selected(old('contract_type', $employee->activeContract?->contract_type ?? 'permanent') === $value)>{{ $label }}</option>@endforeach</select></label>
                <label>تاريخ البداية<input type="date" name="start_date" value="{{ old('start_date', $employee->activeContract?->end_date?->addDay()->toDateString() ?? today()->toDateString()) }}" required></label>
                <label>تاريخ النهاية<input type="date" name="end_date" value="{{ old('end_date') }}"></label>
                <label>نهاية التجربة<input type="date" name="probation_end_date" value="{{ old('probation_end_date') }}"></label>
                <label>ساعات العمل يوميًا<input type="number" name="working_hours_per_day" value="{{ old('working_hours_per_day', 8) }}" min="0.5" max="24" step="0.25" required></label>
                <label>أيام العمل أسبوعيًا<input type="number" name="working_days_per_week" value="{{ old('working_days_per_week', 6) }}" min="1" max="7" required></label>
                <label class="is-wide">ملاحظة<textarea name="note" maxlength="2000">{{ old('note') }}</textarea></label>
                <div class="employee-form-actions"><button class="btn btn-primary" type="submit">{{ $employee->activeContract ? 'تجديد العقد' : 'حفظ العقد' }}</button></div>
            </form>
        </section>
    @endcan

    @if($canViewSalary)
        <section id="salary" class="employee-finance-section">
            <header class="employee-finance-heading"><div><span>SALARY LEDGER</span><h2>الراتب الحالي</h2></div><strong>{{ number_format((float) $currentBasicSalary, 2) }} <small>ر.س</small></strong></header>
            <div class="employee-salary-components">
                @forelse($currentSalaryComponents as $salaryRow)
                    <article><span>{{ $salaryRow->salaryComponent->name_ar }}</span><strong>{{ $salaryRow->salaryComponent->calculation_type === 'fixed' ? number_format((float) $salaryRow->amount, 2).' ر.س' : number_format((float) $salaryRow->percentage, 2).'%' }}</strong><small>من {{ $salaryRow->effective_from->format('Y-m-d') }}</small></article>
                @empty
                    <p class="employee-empty">لا توجد مكونات راتب سارية.</p>
                @endforelse
            </div>
        </section>

        @if($canManageSalary)
            <section class="panel employee-finance-form-card">
                <header><h2>إضافة تغيير راتب مؤرّخ</h2></header>
                <div class="employee-salary-form-grid">
                    @foreach($salaryCatalog as $component)
                        <form method="post" action="{{ route('dashboard.hr.employees.salary.store', [$employee, $component]) }}">
                            @csrf
                            <h3>{{ $component->name_ar }}</h3>
                            @if($component->calculation_type === 'fixed')<label>المبلغ<input type="number" name="amount" min="0.01" step="0.01" required></label>@else<label>النسبة من الأساسي<input type="number" name="percentage" min="0.01" step="0.01" required></label>@endif
                            <label>ساري من<input type="date" name="effective_from" value="{{ today()->toDateString() }}" required></label>
                            <label>سبب التغيير<textarea name="reason" maxlength="1000" required></textarea></label>
                            <button class="btn btn-outline" type="submit">حفظ السجل</button>
                        </form>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="panel employee-finance-table-card">
            <header><h2>تاريخ مكونات الراتب</h2><span>{{ $salaryHistory->count() }}</span></header>
            <div class="employment-table-wrap"><table><thead><tr><th>المكوّن</th><th>القيمة</th><th>من</th><th>إلى</th></tr></thead><tbody>@forelse($salaryHistory as $row)<tr><td>{{ $row->salaryComponent->name_ar }}</td><td>{{ $row->salaryComponent->calculation_type === 'fixed' ? number_format((float) $row->amount, 2).' ر.س' : number_format((float) $row->percentage, 2).'%' }}</td><td>{{ $row->effective_from->format('Y-m-d') }}</td><td>{{ $row->effective_to?->format('Y-m-d') ?? 'مستمر' }}</td></tr>@empty<tr><td colspan="4" class="employee-empty">لا يوجد تاريخ راتب.</td></tr>@endforelse</tbody></table></div>
        </section>

        <section id="payroll-history" class="panel employee-finance-table-card">
            <header><h2>تاريخ مسيرات الرواتب</h2><span>{{ $employee->payrollRunEmployees->count() }}</span></header>
            <div class="employment-table-wrap"><table><thead><tr><th>الفترة</th><th>الأساسي</th><th>الاستحقاقات</th><th>الخصومات</th><th>الصافي</th><th>الحالة</th><th></th></tr></thead><tbody>@forelse($employee->payrollRunEmployees as $snapshot)<tr><td>{{ config("payroll.months.{$snapshot->payrollRun->period_month}") }} {{ $snapshot->payrollRun->period_year }}</td><td>{{ number_format((float) $snapshot->basic_salary, 2) }}</td><td>{{ number_format((float) $snapshot->total_earnings, 2) }}</td><td>{{ number_format((float) $snapshot->total_deductions, 2) }}</td><td><strong>{{ number_format((float) $snapshot->net_salary, 2) }}</strong></td><td>{{ config("payroll.run_statuses.{$snapshot->payrollRun->status}") }}</td><td><a class="btn btn-outline btn-sm" href="{{ route('dashboard.payslips.show', $snapshot) }}">كشف</a></td></tr>@empty<tr><td colspan="7" class="employee-empty">لم يدخل الموظف في أي مسير بعد.</td></tr>@endforelse</tbody></table></div>
        </section>

        <section id="loans" class="employee-finance-split">
            @if($canManageFinance)
                <article class="panel employee-finance-form-card"><header><h2>طلب سلفة</h2></header><form method="post" action="{{ route('dashboard.hr.employees.loans.store', $employee) }}" class="employee-single-form">@csrf<label>المبلغ<input type="number" name="amount" min="0.01" step="0.01" required></label><label>عدد الأقساط<input type="number" name="instalments_count" min="1" max="120" required></label><label>أول شهر استقطاع<input type="month" name="first_instalment_month" value="{{ now()->addMonth()->format('Y-m') }}" required></label><label>السبب<textarea name="reason" maxlength="1000" required></textarea></label><button class="btn btn-primary" type="submit">تسجيل الطلب</button></form></article>
            @endif
            <article class="panel employee-finance-table-card"><header><h2>السلف والأقساط</h2><span>{{ $employee->loans->count() }}</span></header><div class="employee-loan-list">@forelse($employee->loans as $loan)<details><summary><b dir="ltr">{{ $loan->loan_number }}</b><span>{{ number_format((float) $loan->amount, 2) }} ر.س</span><em>{{ config("payroll.loan_statuses.{$loan->status}", $loan->status) }}</em></summary><div><p>{{ $loan->reason }}</p><ol>@foreach($loan->instalments as $instalment)<li><span>{{ $instalment->due_month }}/{{ $instalment->due_year }}</span><strong>{{ number_format((float) $instalment->amount, 2) }}</strong><small>{{ $instalment->status === 'deducted' ? 'مستقطع' : 'مجدول' }}</small></li>@endforeach</ol>@can('approve', $loan) @if($loan->status === 'requested')<form method="post" action="{{ route('dashboard.payroll.loans.approve', $loan) }}">@csrf<button class="btn btn-primary btn-sm" type="submit">اعتماد وجدولة</button></form>@endif @endcan</div></details>@empty<p class="employee-empty">لا توجد سلف.</p>@endforelse</div></article>
        </section>

        <section id="adjustments" class="employee-finance-split">
            @if($canManageFinance)
                <article class="panel employee-finance-form-card"><header><h2>تعديل مالي</h2></header><form method="post" action="{{ route('dashboard.hr.employees.adjustments.store', $employee) }}" class="employee-single-form">@csrf<label>النوع<select name="adjustment_type" required><option value="earning">استحقاق</option><option value="deduction">خصم</option></select></label><label>مكوّن الراتب<select name="salary_component_id"><option value="">بدون مكوّن</option>@foreach($salaryCatalog as $component)<option value="{{ $component->id }}">{{ $component->name_ar }}</option>@endforeach</select></label><label>الفترة<div class="employee-inline-fields"><select name="period_month" required>@foreach(config('payroll.months') as $number => $label)<option value="{{ $number }}" @selected($number === now()->month)>{{ $label }}</option>@endforeach</select><input type="number" name="period_year" value="{{ now()->year }}" min="2020" max="2100" required></div></label><label>المبلغ<input type="number" name="amount" min="0.01" step="0.01" required></label><label>السبب<textarea name="reason" maxlength="1000" required></textarea></label><button class="btn btn-primary" type="submit">حفظ كمسودة</button></form></article>
            @endif
            <article class="panel employee-finance-table-card"><header><h2>الاستحقاقات والخصومات</h2><span>{{ $employee->payrollAdjustments->count() }}</span></header><div class="employment-table-wrap"><table><thead><tr><th>الفترة</th><th>النوع</th><th>المبلغ</th><th>السبب</th><th>الحالة</th><th></th></tr></thead><tbody>@forelse($employee->payrollAdjustments as $adjustment)<tr><td>{{ $adjustment->period_month }}/{{ $adjustment->period_year }}</td><td>{{ $adjustment->adjustment_type === 'earning' ? 'استحقاق' : 'خصم' }}</td><td>{{ number_format((float) $adjustment->amount, 2) }}</td><td>{{ $adjustment->reason }}</td><td>{{ config("payroll.adjustment_statuses.{$adjustment->status}") }}</td><td>@can('approve', $adjustment) @if($adjustment->status === 'draft')<form method="post" action="{{ route('dashboard.payroll.adjustments.approve', $adjustment) }}">@csrf<button class="btn btn-outline btn-sm" type="submit">اعتماد</button></form>@endif @endcan</td></tr>@empty<tr><td colspan="6" class="employee-empty">لا توجد تعديلات مالية.</td></tr>@endforelse</tbody></table></div></article>
        </section>

        <section id="bank" class="panel employee-finance-form-card"><header><h2>البيانات البنكية</h2></header><div class="employee-bank-layout"><dl><div><dt>البنك</dt><dd>{{ $employee->bank?->name ?? '—' }}</dd></div><div><dt>اسم صاحب الحساب</dt><dd>{{ $employee->account_holder_name ?? '—' }}</dd></div><div><dt>الآيبان</dt><dd dir="ltr">{{ $employee->iban ?? '—' }}</dd></div></dl>@if($canManageSalary)<form method="post" action="{{ route('dashboard.hr.employees.bank-details.update', $employee) }}" class="employee-single-form">@csrf @method('PATCH')<label>البنك<select name="bank_id"><option value="">بدون بنك</option>@foreach($banks as $bank)<option value="{{ $bank->id }}" @selected((int)old('bank_id',$employee->bank_id) === $bank->id)>{{ $bank->name }}</option>@endforeach</select></label><label>اسم صاحب الحساب<input name="account_holder_name" value="{{ old('account_holder_name',$employee->account_holder_name) }}" maxlength="190"></label><label>الآيبان<input name="iban" value="{{ old('iban',$employee->iban) }}" maxlength="34" dir="ltr"></label><button class="btn btn-primary" type="submit">حفظ البيانات البنكية</button></form>@endif</div></section>

        <section id="documents" class="employee-finance-split">
            @if($canManageSalary)<article class="panel employee-finance-form-card"><header><h2>رفع مستند</h2></header><form method="post" action="{{ route('dashboard.hr.employees.documents.store',$employee) }}" enctype="multipart/form-data" class="employee-single-form">@csrf<label>نوع المستند<select name="document_type" required>@foreach(config('employment.employee_document_types') as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label><label>رقم المستند<input name="document_number" maxlength="100"></label><label>تاريخ الإصدار<input type="date" name="issue_date"></label><label>تاريخ الانتهاء<input type="date" name="expiry_date"></label><label>الملف<input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required></label><button class="btn btn-primary" type="submit">رفع المستند</button></form></article>@endif
            <article class="panel employee-finance-table-card"><header><h2>المستندات الخاصة</h2><span>{{ $employee->documents->count() }}</span></header><div class="employment-table-wrap"><table><thead><tr><th>النوع</th><th>الرقم</th><th>الإصدار</th><th>الانتهاء</th><th>الملف</th><th></th></tr></thead><tbody>@forelse($employee->documents as $document)<tr><td>{{ config("employment.employee_document_types.{$document->document_type}") }}</td><td dir="ltr">{{ $document->document_number ?? '—' }}</td><td>{{ $document->issue_date?->format('Y-m-d') ?? '—' }}</td><td>{{ $document->expiry_date?->format('Y-m-d') ?? '—' }}</td><td>{{ $document->original_name }}</td><td><a class="btn btn-outline btn-sm" href="{{ route('dashboard.hr.employees.documents.download',[$employee,$document]) }}">تنزيل</a></td></tr>@empty<tr><td colspan="6" class="employee-empty">لا توجد مستندات.</td></tr>@endforelse</tbody></table></div></article>
        </section>
    @endif

    <section id="audit" class="panel employee-audit-card"><header><h2>سجل التغييرات</h2><span>{{ $audits->count() }}</span></header><ol>@forelse($audits as $audit)<li><time>{{ $audit->created_at->format('Y-m-d H:i') }}</time><strong>{{ $audit->user?->full_name ?? 'النظام' }}</strong><span>{{ $audit->action }}</span>@if($audit->reason)<p>{{ $audit->reason }}</p>@endif</li>@empty<li class="employee-empty">لا توجد تغييرات مسجلة.</li>@endforelse</ol></section>
</div>
@endsection
