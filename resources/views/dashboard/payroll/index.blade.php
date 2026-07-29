@extends('layouts.dashboard')

@section('title', 'الرواتب والمستحقات')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/payroll.css') }}">
@endpush

@section('content')
<div class="payroll-ledger">
    <header class="payroll-command">
        <div><span>FINANCIAL CONTROL / {{ $year }}</span><h1>الرواتب والمستحقات</h1><p>مسير شهري محكوم بسجلات الحضور، مع تثبيت السجلات المصروفة ومنع تعديلها لاحقًا.</p></div>
        <div class="payroll-period-tools">
            <form method="get" class="payroll-period-form"><label>الشهر<select name="month">@foreach(config('payroll.months') as $number => $name)<option value="{{ $number }}" @selected($number === $month)>{{ $name }}</option>@endforeach</select></label><label>السنة<input type="number" name="year" min="2000" max="2100" value="{{ $year }}"></label><button class="btn btn-outline" type="submit">عرض الفترة</button></form>
            @if($payrollRows->isEmpty())<form method="post" action="{{ route('dashboard.payroll.generate') }}">@csrf<input type="hidden" name="month" value="{{ $month }}"><input type="hidden" name="year" value="{{ $year }}"><button class="btn btn-primary" type="submit">توليد المسير</button></form>@endif
        </div>
    </header>

    <section class="payroll-vitals">
        <article class="primary"><span>صافي المسير</span><strong>{{ number_format((float) $stats->net_total, 2) }}</strong><small>ر.س</small></article>
        <article><span>الأساسي</span><strong>{{ number_format((float) $stats->base_total, 2) }}</strong></article>
        <article><span>البدلات</span><strong>{{ number_format((float) $stats->allowance_total, 2) }}</strong></article>
        <article><span>الإضافي</span><strong>{{ number_format((float) $stats->overtime_total, 2) }}</strong><small>{{ number_format((float) $stats->overtime_hours, 1) }} ساعة</small></article>
        <article><span>المكافآت</span><strong>{{ number_format((float) $stats->bonus_total, 2) }}</strong></article>
        <article class="deduction"><span>الخصومات</span><strong>{{ number_format((float) $stats->deduction_total, 2) }}</strong></article>
        <article><span>حالة الصرف</span><strong>{{ $stats->paid_count }} / {{ $stats->total_count }}</strong><small>مصروف</small></article>
    </section>

    <section class="payroll-register">
        <header><div><span>MONTHLY REGISTER</span><h2>مسير {{ config("payroll.months.{$month}") }} {{ $year }}</h2></div><b>{{ $payrollRows->count() }} موظف</b></header>
        <div class="payroll-table-wrap"><table class="payroll-table"><thead><tr><th>الموظف</th><th>الأساسي</th><th>الإضافي</th><th>البدلات</th><th>المكافآت</th><th>الخصومات</th><th>الصافي</th><th>الحالة</th><th>الإجراء</th></tr></thead><tbody>
        @forelse($payrollRows as $payroll)
            <tr class="{{ $payroll->paid_status === 'paid' ? 'is-paid' : '' }}">
                <td><strong>{{ $payroll->employee->user->full_name }}</strong><small>#{{ str_pad((string) $payroll->id, 5, '0', STR_PAD_LEFT) }}</small></td><td>{{ number_format((float) $payroll->base_salary, 2) }}</td><td>{{ number_format((float) $payroll->overtime_amount, 2) }}<small>{{ $payroll->overtime_hours }} س</small></td>
                @if($payroll->paid_status === 'pending')
                    <td colspan="3"><form id="payroll-update-{{ $payroll->id }}" method="post" action="{{ route('dashboard.payroll.update', $payroll) }}" class="payroll-adjustments">@csrf @method('PUT')<label><span>بدلات</span><input type="number" step="0.01" min="0" name="allowances" value="{{ $payroll->allowances }}" required></label><label><span>مكافآت</span><input type="number" step="0.01" min="0" name="bonuses" value="{{ $payroll->bonuses }}" required></label><label><span>خصومات</span><input type="number" step="0.01" min="0" name="deductions" value="{{ $payroll->deductions }}" required></label></form></td>
                @else<td>{{ number_format((float) $payroll->allowances, 2) }}</td><td>{{ number_format((float) $payroll->bonuses, 2) }}</td><td>{{ number_format((float) $payroll->deductions, 2) }}</td>@endif
                <td><strong class="net-amount">{{ number_format((float) $payroll->net_salary, 2) }}</strong></td><td><span class="payroll-state state-{{ $payroll->paid_status }}">{{ $payroll->paid_status === 'paid' ? 'مصروف' : 'معلّق' }}</span>@if($payroll->paid_at)<small>{{ $payroll->paid_at->format('Y/m/d H:i') }}</small>@endif</td>
                <td>@if($payroll->paid_status === 'pending')<div class="payroll-actions"><button class="btn btn-outline btn-sm" type="submit" form="payroll-update-{{ $payroll->id }}">حفظ</button><form method="post" action="{{ route('dashboard.payroll.pay', $payroll) }}" data-confirm="تأكيد صرف الراتب وتثبيت السجل؟">@csrf @method('PATCH')<button class="btn btn-primary btn-sm" type="submit">صرف</button></form></div>@else<span class="locked-record">مغلق</span>@endif</td>
            </tr>
        @empty<tr><td colspan="9" class="payroll-empty">لم يُولّد مسير لهذه الفترة بعد.</td></tr>@endforelse
        </tbody></table></div>
    </section>

    <section class="payroll-comparison"><header><span>6 PERIOD TREND</span><h2>المقارنة بين الأشهر</h2></header><div class="comparison-strip">@forelse($monthlyComparison as $period)<article><small>{{ config("payroll.months.{$period->period_month}") }} {{ $period->period_year }}</small><strong>{{ number_format((float) $period->total_net, 2) }}</strong><span>ر.س</span></article>@empty<p>لا توجد بيانات مقارنة بعد.</p>@endforelse</div></section>
</div>
@endsection
