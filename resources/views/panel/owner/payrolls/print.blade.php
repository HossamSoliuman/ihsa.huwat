@extends('layouts.sheet')

@section('title', 'hawat_payroll_'.$payroll->payroll_number)
@section('orientation', 'landscape')
@section('width', '1050px')

@section('content')
    @php
        $lines = $payroll->lines->sortBy([['is_captain', 'desc'], ['member_name', 'asc']]);
        $pct = fn ($v) => rtrim(rtrim(number_format($v, 2), '0'), '.');
    @endphp
    <header class="head">
        <div>
            <h1>{{ $owner->name }}</h1>
            <p>هاتف: <span class="num" dir="ltr">{{ $owner->phone ?? '—' }}</span></p>
        </div>
        <div style="text-align:left">
            <p>رقم المسير: <b class="num">{{ $payroll->payroll_number }}</b></p>
            <p>الحالة: {{ $payroll->paymentStatus?->name ?? '—' }}</p>
        </div>
    </header>

    <div class="title">مسير رواتب {{ $payroll->boat_name }} — {{ $payroll->period_label }}</div>

    <div class="chips">
        <span>الإيراد <b class="num">{{ number_format($payroll->revenue, 2) }}</b></span>
        <span>المصروفات <b class="num">{{ number_format($payroll->expenses, 2) }}</b></span>
        <span>الإهلاك المحمَّل <b class="num">{{ number_format($payroll->depreciation_charged, 2) }}</b>@if ($payroll->depreciation_deferred > 0) (مؤجَّل <span class="num">{{ number_format($payroll->depreciation_deferred, 2) }}</span>)@endif</span>
        <span>صافي الربح <b class="num">{{ number_format($payroll->net_profit, 2) }}</b></span>
        <span>نصيب المالك <span class="num">{{ $pct($payroll->owner_share_percent) }}%</span> <b class="num">{{ number_format($payroll->owner_share, 2) }}</b></span>
        <span>نصيب الطاقم <b class="num">{{ number_format($payroll->crew_pool, 2) }}</b></span>
    </div>

    <table>
        <thead>
            <tr><th>#</th><th>الفرد</th><th>الأجر</th><th>الأساس</th><th>زيادة</th><th>خصم</th><th>سلف</th><th>الصافي</th><th>السداد</th><th>التوقيع</th></tr>
        </thead>
        <tbody>
            @foreach ($lines->values() as $i => $line)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>{{ $line->member_name }}@if ($line->is_captain) <span class="muted">(كابتن)</span>@endif @if ($line->notes)<div class="muted">{{ $line->notes }}</div>@endif</td>
                    <td>
                        {{ $line->payType?->name }}
                        <div class="muted">
                            @if (! $line->payType?->isShare())
                                <span class="num">{{ number_format($line->fixed_salary, 2) }}</span> شهريًا
                            @elseif ($line->custom_share_percent)
                                نسبة خاصة <span class="num">{{ $pct($line->custom_share_percent) }}%</span>
                            @else
                                <span class="num">{{ $pct($line->profit_shares) }}</span> سهم
                            @endif
                        </div>
                    </td>
                    <td class="num">{{ number_format($line->base_amount, 2) }}</td>
                    <td class="num">{{ number_format($line->bonus, 2) }}</td>
                    <td class="num">{{ number_format($line->deduction, 2) }}</td>
                    <td class="num">{{ number_format($line->advances, 2) }}</td>
                    <td class="num" style="font-weight:700">{{ number_format($line->net, 2) }}</td>
                    <td>@if ($line->is_paid)<span class="num">{{ $line->paid_at->format('Y-m-d') }}</span><div class="muted">{{ $line->paymentMethod?->name }}</div>@else — @endif</td>
                    <td style="min-width:90px"></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">الإجمالي</td>
                <td class="num">{{ number_format($lines->sum('base_amount'), 2) }}</td>
                <td class="num">{{ number_format($lines->sum('bonus'), 2) }}</td>
                <td class="num">{{ number_format($lines->sum('deduction'), 2) }}</td>
                <td class="num">{{ number_format($lines->sum('advances'), 2) }}</td>
                <td class="num">{{ number_format($lines->sum('net'), 2) }}</td>
                <td class="num" colspan="2">مسدَّد {{ number_format($lines->sum('paid_amount'), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <p class="muted">نصيب الطاقم = (صافي الربح − نصيب المالك)، ولا يقل عن صفر. صاحب النسبة الخاصة يأخذها من النصيب كله، والباقي بالأسهم. الرواتب الثابتة مرحَّلة مصروفًا على القارب ومحسوبة في المصروفات أعلاه.</p>

    <div class="signs">
        <div><span></span>المحاسب</div>
        <div><span></span>الكابتن</div>
        <div><span></span>المالك</div>
    </div>
@endsection
