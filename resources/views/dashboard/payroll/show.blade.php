@extends('layouts.dashboard')

@section('title', $run->run_number)

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/payroll.css') }}">
@endpush

@section('content')
<div class="payroll-ledger">
    <header class="payroll-run-head">
        <div><a href="{{ route('dashboard.payroll.index') }}">مسيرات الرواتب</a><span dir="ltr">{{ $run->run_number }}</span><h1>{{ config("payroll.months.{$run->period_month}") }} {{ $run->period_year }}</h1></div>
        <div class="payroll-run-state"><span class="payroll-state state-{{ $run->status }}">{{ config("payroll.run_statuses.{$run->status}") }}</span><small>{{ $run->period_start->format('Y-m-d') }} — {{ $run->period_end->format('Y-m-d') }}</small></div>
    </header>

    <section class="payroll-stagebar" aria-label="مراحل المسير">
        @foreach(['draft' => 'المسودة', 'calculated' => 'الاحتساب', 'approved' => 'الاعتماد', 'paid' => 'الصرف', 'closed' => 'الإغلاق'] as $value => $label)
            <span class="{{ array_search($value, array_keys(config('payroll.run_statuses')), true) <= array_search($run->status, array_keys(config('payroll.run_statuses')), true) ? 'is-done' : '' }}">{{ $label }}</span>
        @endforeach
    </section>

    <section class="payroll-vitals payroll-vitals-run">
        <article class="primary"><span>صافي المسير</span><strong>{{ number_format((float) $run->net_total, 2) }}</strong><small>ر.س</small></article>
        <article><span>الاستحقاقات</span><strong>{{ number_format((float) $run->total_earnings, 2) }}</strong></article>
        <article class="deduction"><span>الخصومات</span><strong>{{ number_format((float) $run->total_deductions, 2) }}</strong></article>
        <article><span>الموظفون</span><strong>{{ number_format($run->employees_count) }}</strong></article>
    </section>

    <section class="payroll-action-deck">
        @if(in_array($run->status, [App\Models\PayrollRun::STATUS_DRAFT, App\Models\PayrollRun::STATUS_CALCULATED], true))
            @can('calculate', $run)<form method="post" action="{{ route('dashboard.payroll.runs.calculate', $run) }}">@csrf<button class="btn btn-primary" type="submit">{{ $run->status === 'calculated' ? 'إعادة الاحتساب' : 'احتساب المسير' }}</button></form>@endcan
        @endif
        @if($run->status === App\Models\PayrollRun::STATUS_CALCULATED)
            @can('approve', $run)<form method="post" action="{{ route('dashboard.payroll.runs.approve', $run) }}" data-confirm="اعتماد المسير يثبت التعديلات والأقساط. متابعة؟">@csrf<button class="btn btn-primary" type="submit">اعتماد المسير</button></form>@endcan
        @endif
        @if($run->status === App\Models\PayrollRun::STATUS_APPROVED)
            @can('markPaid', $run)<form method="post" action="{{ route('dashboard.payroll.runs.paid', $run) }}" class="payroll-payment-form">@csrf<label>تاريخ الصرف<input type="date" name="payment_date" value="{{ old('payment_date', today()->toDateString()) }}" required></label><label>مرجع الدفع<input name="payment_reference" value="{{ old('payment_reference') }}" maxlength="190" required></label><label>ملاحظة<input name="note" value="{{ old('note') }}" maxlength="2000"></label><button class="btn btn-primary" type="submit">تسجيل الصرف</button></form>@endcan
        @endif
        @if($run->status === App\Models\PayrollRun::STATUS_PAID)
            @can('close', $run)<form method="post" action="{{ route('dashboard.payroll.runs.close', $run) }}" data-confirm="إغلاق المسير نهائيًا؟">@csrf<button class="btn btn-primary" type="submit">إغلاق المسير</button></form>@endcan
        @endif
        @if($run->status === App\Models\PayrollRun::STATUS_DRAFT)
            @can('delete', $run)<form method="post" action="{{ route('dashboard.payroll.runs.destroy', $run) }}" data-confirm="حذف مسودة المسير؟">@csrf @method('DELETE')<button class="btn btn-danger" type="submit">حذف المسودة</button></form>@endcan
        @endif
    </section>

    @if($run->issues->isNotEmpty())
        <section class="payroll-register payroll-issues-panel">
            <header><div><span>VALIDATION</span><h2>ملاحظات الاحتساب</h2></div><b>{{ $run->issues->count() }}</b></header>
            <div class="payroll-table-wrap"><table class="payroll-table"><thead><tr><th>المستوى</th><th>الموظف</th><th>الرمز</th><th>الملاحظة</th></tr></thead><tbody>@foreach($run->issues as $issue)<tr><td><span class="payroll-issue-count is-{{ $issue->level }}">{{ $issue->level === 'error' ? 'خطأ' : 'تنبيه' }}</span></td><td>{{ $issue->employee?->user?->full_name ?? 'المسير' }}</td><td dir="ltr">{{ $issue->code }}</td><td>{{ $issue->message_ar }}</td></tr>@endforeach</tbody></table></div>
        </section>
    @endif

    <section class="payroll-register payroll-employee-register">
        <header><div><span>EMPLOYEE SNAPSHOTS</span><h2>تفاصيل الموظفين</h2></div><b>{{ $run->employees->count() }}</b></header>
        <div class="payroll-table-wrap"><table class="payroll-table"><thead><tr><th>الموظف</th><th>القسم</th><th>الأساسي</th><th>الاستحقاقات</th><th>الخصومات</th><th>الصافي</th><th>الحالة</th><th></th></tr></thead><tbody>
        @forelse($run->employees as $snapshot)
            <tr><td><strong>{{ $snapshot->employee_name }}</strong><small dir="ltr">{{ $snapshot->employee_number }}</small></td><td>{{ $snapshot->department_name ?? '—' }}<small>{{ $snapshot->job_title_name ?? '—' }}</small></td><td>{{ number_format((float) $snapshot->basic_salary, 2) }}</td><td>{{ number_format((float) $snapshot->total_earnings, 2) }}</td><td class="money-negative">{{ number_format((float) $snapshot->total_deductions, 2) }}</td><td><strong class="net-amount">{{ number_format((float) $snapshot->net_salary, 2) }}</strong></td><td><span class="snapshot-state is-{{ $snapshot->status }}">{{ $snapshot->status === 'ok' ? 'سليم' : ($snapshot->status === 'warning' ? 'تنبيه' : 'خطأ') }}</span></td><td><a class="btn btn-outline btn-sm" href="{{ route('dashboard.payroll.runs.employees.show', [$run, $snapshot]) }}">تفاصيل</a></td></tr>
            <tr class="payroll-items-row"><td colspan="8"><details><summary>بنود الاحتساب ({{ $snapshot->items->count() }})</summary><div class="payroll-item-grid">@foreach($snapshot->items as $item)<article data-type="{{ $item->item_type }}"><span>{{ $item->label_ar }}</span><strong>{{ number_format((float) $item->amount, 2) }}</strong><small>{{ $item->calculation_details['formula_ar'] ?? '' }}</small></article>@endforeach</div></details></td></tr>
        @empty<tr><td colspan="8" class="payroll-empty">اضغط «احتساب المسير» لإنشاء سجلات الموظفين.</td></tr>@endforelse
        </tbody></table></div>
    </section>

    @if($audits->isNotEmpty())
        <section class="payroll-audit"><header><span>AUDIT TRAIL</span><h2>سجل الإجراءات</h2></header><ol>@foreach($audits as $audit)<li><time>{{ $audit->created_at->format('Y-m-d H:i') }}</time><strong>{{ $audit->user?->full_name ?? 'النظام' }}</strong><span>{{ $audit->action }}</span>@if($audit->reason)<p>{{ $audit->reason }}</p>@endif</li>@endforeach</ol></section>
    @endif
</div>
@endsection
