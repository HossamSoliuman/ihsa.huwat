@extends('layouts.dashboard')

@section('title', 'مسيرات الرواتب')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/payroll.css') }}">
@endpush

@section('content')
<div class="payroll-ledger">
    <header class="payroll-command">
        <div><span>PAYROLL CONTROL</span><h1>مسيرات الرواتب</h1></div>
        @can('create', App\Models\PayrollRun::class)
            <form method="post" action="{{ route('dashboard.payroll.runs.store') }}" class="payroll-create-run">
                @csrf
                <label>الشهر<select name="period_month" required>@foreach(config('payroll.months') as $number => $label)<option value="{{ $number }}" @selected((int) old('period_month', now()->month) === $number)>{{ $label }}</option>@endforeach</select></label>
                <label>السنة<input type="number" name="period_year" value="{{ old('period_year', now()->year) }}" min="2020" max="2100" required></label>
                <label class="is-note">ملاحظة<input name="note" value="{{ old('note') }}" maxlength="2000"></label>
                <button class="btn btn-primary" type="submit">إنشاء مسير</button>
            </form>
        @endcan
    </header>

    <section class="payroll-vitals payroll-vitals-compact">
        <article class="primary"><span>إجمالي الصافي</span><strong>{{ number_format((float) $summary->net_total, 2) }}</strong><small>ر.س</small></article>
        <article><span>عدد المسيرات</span><strong>{{ number_format((int) $summary->runs_count) }}</strong></article>
        <article><span>سجلات الموظفين</span><strong>{{ number_format((int) $summary->employees_count) }}</strong></article>
        <article><span>المصروف والمغلق</span><strong>{{ number_format((int) $summary->sealed_count) }}</strong></article>
    </section>

    <section class="payroll-register">
        <header>
            <div><span>RUN REGISTER</span><h2>سجل المسيرات</h2></div>
            <form method="get" class="payroll-filter-form">
                <select name="year"><option value="">كل السنوات</option>@foreach($years as $year)<option value="{{ $year }}" @selected((string) ($filters['year'] ?? '') === (string) $year)>{{ $year }}</option>@endforeach</select>
                <select name="status"><option value="">كل الحالات</option>@foreach(config('payroll.run_statuses') as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
                <button class="btn btn-outline btn-sm" type="submit">تصفية</button>
            </form>
        </header>
        <div class="payroll-table-wrap"><table class="payroll-table payroll-runs-table"><thead><tr><th>رقم المسير</th><th>الفترة</th><th>الموظفون</th><th>الاستحقاقات</th><th>الخصومات</th><th>الصافي</th><th>الملاحظات</th><th>الحالة</th><th></th></tr></thead><tbody>
        @forelse($runs as $run)
            <tr>
                <td><strong dir="ltr">{{ $run->run_number }}</strong><small>{{ $run->creator->full_name }}</small></td>
                <td>{{ config("payroll.months.{$run->period_month}") }} {{ $run->period_year }}</td>
                <td>{{ number_format($run->employees_count) }}</td>
                <td>{{ number_format((float) $run->total_earnings, 2) }}</td>
                <td class="money-negative">{{ number_format((float) $run->total_deductions, 2) }}</td>
                <td><strong class="net-amount">{{ number_format((float) $run->net_total, 2) }}</strong></td>
                <td><span class="payroll-issue-count is-error">{{ $run->errors_count }}</span><span class="payroll-issue-count is-warning">{{ $run->warnings_count }}</span></td>
                <td><span class="payroll-state state-{{ $run->status }}">{{ config("payroll.run_statuses.{$run->status}") }}</span></td>
                <td><a class="btn btn-outline btn-sm" href="{{ route('dashboard.payroll.runs.show', $run) }}">فتح</a></td>
            </tr>
        @empty
            <tr><td colspan="9" class="payroll-empty">لا توجد مسيرات رواتب.</td></tr>
        @endforelse
        </tbody></table></div>
        <div class="payroll-pagination">{{ $runs->links() }}</div>
    </section>
</div>
@endsection
