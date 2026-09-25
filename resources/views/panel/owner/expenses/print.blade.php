@extends('layouts.sheet')

@section('title', 'hawat_expense_'.$expense->expense_number)

@section('content')
    <header class="head">
        <div>
            <h1>{{ $expense->owner->name }}</h1>
            <p>هاتف: <span class="num" dir="ltr">{{ $expense->owner->phone ?? '—' }}</span></p>
        </div>
        <div style="text-align:left">
            <p>رقم السند: <b class="num">{{ $expense->expense_number }}</b></p>
            <p>التاريخ: <span class="num">{{ $expense->date->format('Y-m-d') }}</span></p>
        </div>
    </header>

    <div class="title">سند صرف</div>

    <div class="boxes">
        <div>
            <h3>المصروف</h3>
            <dl>
                <dt>المجموعة</dt><dd>{{ $expense->category?->group?->name ?? '—' }}</dd>
                <dt>الفئة</dt><dd>{{ $expense->category?->name ?? '—' }}</dd>
                <dt>الوصف</dt><dd>{{ $expense->description ?? '—' }}</dd>
                <dt>القارب</dt><dd>{{ $expense->boat?->name ?? 'عام' }}</dd>
                @if ($expense->trip)<dt>الرحلة</dt><dd class="num">{{ $expense->trip->trip_number }}</dd>@endif
            </dl>
        </div>
        <div>
            <h3>الدفع</h3>
            <dl>
                <dt>المورد</dt><dd>{{ $expense->vendor?->name ?? '—' }}</dd>
                <dt>طريقة الدفع</dt><dd>{{ $expense->paymentMethod?->name ?? '—' }}</dd>
                <dt>الحالة</dt><dd>{{ $expense->paymentStatus?->name ?? '—' }}</dd>
                <dt>المدفوع</dt><dd class="num">{{ number_format($expense->paid_amount, 2) }} ر.س</dd>
                <dt>المتبقي</dt><dd class="num">{{ number_format($expense->remaining, 2) }} ر.س</dd>
            </dl>
        </div>
    </div>

    <div class="totals">
        <div><span>المبلغ</span><span class="num">{{ number_format($expense->subtotal, 2) }}</span></div>
        @if ($expense->discount > 0)
            <div><span>الخصم @if ($expense->discount_pct)(<span class="num">{{ rtrim(rtrim(number_format($expense->discount_pct, 2), '0'), '.') }}%</span>)@endif</span><span class="num">− {{ number_format($expense->discount, 2) }}</span></div>
        @endif
        <div><span>ضريبة القيمة المضافة (<span class="num">{{ rtrim(rtrim(number_format($expense->vat_rate, 2), '0'), '.') }}%</span>)</span><span class="num">{{ number_format($expense->vat_amount, 2) }}</span></div>
        <div class="grand"><span>الإجمالي</span><span class="num">{{ number_format($expense->total, 2) }} ر.س</span></div>
    </div>

    @if ($expense->notes)
        <p style="margin-top:16px"><b>ملاحظات:</b> {{ $expense->notes }}</p>
    @endif

    <div class="signs">
        <div><span></span>المستلم</div>
        <div><span></span>المحاسب</div>
        <div><span></span>المعتمد</div>
    </div>
@endsection
