@extends('layouts.sheet')

@section('title', 'hawat_statement_'.$fisher->id)
@section('orientation', 'landscape')
@section('width', '1050px')

@section('content')
    <header class="head">
        <div>
            <h1>{{ $owner->name }}</h1>
            <p>هاتف: <span class="num" dir="ltr">{{ $owner->phone ?? '—' }}</span></p>
        </div>
        <div style="text-align:left">
            <p>التاريخ: <span class="num">{{ now()->format('Y-m-d') }}</span></p>
        </div>
    </header>

    <div class="title">كشف حساب {{ $fisher->is_captain ? 'كابتن' : 'فرد طاقم' }}</div>

    <div class="boxes">
        <div>
            <h3>الفرد</h3>
            <dl>
                <dt>الاسم</dt><dd>{{ $fisher->name }}</dd>
                <dt>الهوية</dt><dd class="num">{{ $fisher->national_id ?? '—' }}</dd>
                <dt>القارب</dt><dd>{{ $fisher->boat?->name ?? '—' }}</dd>
                <dt>نوع الأجر</dt><dd>{{ $fisher->payType?->name ?? 'لم يُضبط' }}</dd>
            </dl>
        </div>
        <div>
            <h3>الملخص</h3>
            <dl>
                <dt>المستحق في المسيرات</dt><dd class="num">{{ number_format($totals['earned'], 2) }} ر.س</dd>
                <dt>المصروف نقدًا</dt><dd class="num">{{ number_format($totals['paid'], 2) }} ر.س</dd>
                <dt>السلف المأخوذة</dt><dd class="num">{{ number_format($totals['advances_taken'], 2) }} ر.س</dd>
                <dt>سلف لم تُخصم بعد</dt><dd class="num">{{ number_format($totals['advances_outstanding'], 2) }} ر.س</dd>
                <dt>صافي غير مسدَّد</dt><dd class="num">{{ number_format($totals['unpaid'], 2) }} ر.س</dd>
            </dl>
        </div>
    </div>

    <h3 style="font-size:12px;color:#1d6fb8;margin-bottom:6px">المسيرات</h3>
    <table>
        <thead>
            <tr><th>المسير</th><th>الشهر</th><th>القارب</th><th>الأساس</th><th>النوع</th><th>زيادة</th><th>خصم</th><th>المستحق</th><th>سلف مخصومة</th><th>الصافي</th><th>السداد</th></tr>
        </thead>
        <tbody>
            @forelse ($lines as $line)
                <tr>
                    <td class="num">{{ $line->payroll->payroll_number }}</td>
                    <td>{{ $line->payroll->period_label }}</td>
                    <td>{{ $line->payroll->boat_name }}</td>
                    <td class="num">{{ number_format($line->base_amount, 2) }}</td>
                    <td>{{ $line->payType?->name }}@if ($line->custom_share_percent)<div class="muted">نسبة خاصة {{ rtrim(rtrim(number_format($line->custom_share_percent, 2), '0'), '.') }}%</div>@elseif ($line->payType?->isShare())<div class="muted">{{ rtrim(rtrim(number_format($line->profit_shares, 2), '0'), '.') }} سهم</div>@endif</td>
                    <td class="num">{{ number_format($line->bonus, 2) }}</td>
                    <td class="num">{{ number_format($line->deduction, 2) }}</td>
                    <td class="num">{{ number_format($line->gross, 2) }}</td>
                    <td class="num">{{ number_format($line->advances, 2) }}</td>
                    <td class="num" style="font-weight:700">{{ number_format($line->net, 2) }}</td>
                    <td>@if ($line->is_paid)<span class="num">{{ $line->paid_at->format('Y-m-d') }}</span><div class="muted">{{ $line->paymentMethod?->name }}</div>@else غير مسدَّد @endif</td>
                </tr>
            @empty
                <tr><td colspan="11" class="muted" style="text-align:center">لا مسيرات</td></tr>
            @endforelse
        </tbody>
        @if ($lines->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="7">الإجمالي</td>
                    <td class="num">{{ number_format($totals['earned'], 2) }}</td>
                    <td class="num">{{ number_format($lines->sum('advances'), 2) }}</td>
                    <td class="num">{{ number_format($lines->sum('net'), 2) }}</td>
                    <td class="num">{{ number_format($totals['paid'], 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <h3 style="font-size:12px;color:#1d6fb8;margin-bottom:6px">السلف</h3>
    <table>
        <thead>
            <tr><th>التاريخ</th><th>المبلغ</th><th>طريقة الدفع</th><th>ملاحظات</th></tr>
        </thead>
        <tbody>
            @forelse ($advances as $advance)
                <tr>
                    <td class="num">{{ $advance->date->format('Y-m-d') }}</td>
                    <td class="num">{{ number_format($advance->amount, 2) }}</td>
                    <td>{{ $advance->paymentMethod?->name ?? '—' }}</td>
                    <td>{{ $advance->notes ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted" style="text-align:center">لا سلف</td></tr>
            @endforelse
        </tbody>
        @if ($advances->isNotEmpty())
            <tfoot>
                <tr><td>الإجمالي</td><td class="num">{{ number_format($totals['advances_taken'], 2) }}</td><td colspan="2">المخصوم منها في مسيرات مسدَّدة: <span class="num">{{ number_format($totals['advances_settled'], 2) }}</span></td></tr>
            </tfoot>
        @endif
    </table>

    <div class="signs">
        <div><span></span>الفرد</div>
        <div><span></span>المحاسب</div>
        <div><span></span>المالك</div>
    </div>
@endsection
