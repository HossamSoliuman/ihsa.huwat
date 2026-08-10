@extends('layouts.dashboard')

@section('title', 'كشف راتب '.$snapshot->employee_name)

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/payroll.css') }}">
@endpush

@section('content')
<div class="payslip-shell">
    <div class="payslip-toolbar"><a class="btn btn-outline" href="{{ url()->previous() }}">رجوع</a><button class="btn btn-primary" type="button" onclick="window.print()">طباعة الكشف</button></div>
    <article class="payslip-paper">
        <header><div><span>منصة حوات</span><h1>كشف راتب</h1></div><div><b dir="ltr">{{ $snapshot->payrollRun->run_number }}</b><small>{{ config("payroll.months.{$snapshot->payrollRun->period_month}") }} {{ $snapshot->payrollRun->period_year }}</small></div></header>
        <section class="payslip-identity"><div><span>الموظف</span><strong>{{ $snapshot->employee_name }}</strong><small dir="ltr">{{ $snapshot->employee_number }}</small></div><dl><div><dt>القسم</dt><dd>{{ $snapshot->department_name ?? '—' }}</dd></div><div><dt>المسمى</dt><dd>{{ $snapshot->job_title_name ?? '—' }}</dd></div><div><dt>الميناء</dt><dd>{{ $snapshot->port_name ?? '—' }}</dd></div><div><dt>البنك</dt><dd>{{ $snapshot->employee->bank?->name ?? '—' }}</dd></div><div><dt>الآيبان</dt><dd dir="ltr">{{ $snapshot->employee->iban ?? '—' }}</dd></div><div><dt>مرجع الدفع</dt><dd dir="ltr">{{ $snapshot->payrollRun->payment_reference ?? '—' }}</dd></div></dl></section>
        <section class="payslip-columns"><div><h2>الاستحقاقات</h2>@foreach($earnings as $item)<p><span>{{ $item->label_ar }}</span><strong>{{ number_format((float) $item->amount, 2) }}</strong></p>@endforeach<div class="payslip-subtotal"><span>الإجمالي</span><strong>{{ number_format((float) $snapshot->total_earnings, 2) }}</strong></div></div><div><h2>الخصومات</h2>@forelse($deductions as $item)<p><span>{{ $item->label_ar }}</span><strong>{{ number_format((float) $item->amount, 2) }}</strong></p>@empty<p><span>لا توجد خصومات</span><strong>0.00</strong></p>@endforelse<div class="payslip-subtotal"><span>الإجمالي</span><strong>{{ number_format((float) $snapshot->total_deductions, 2) }}</strong></div></div></section>
        <footer><span>صافي الراتب</span><strong>{{ number_format((float) $snapshot->net_salary, 2) }}</strong><b>ر.س</b></footer>
    </article>
</div>
@endsection
