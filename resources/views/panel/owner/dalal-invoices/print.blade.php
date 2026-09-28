@extends('layouts.sheet')

@section('title', 'hawat_dalal_invoice_'.$review->sale->invoice_number)
@section('orientation', 'portrait')

@section('content')
    @php
        $sale = $review->sale;
        $profile = $review->dalal?->dalalProfile;
    @endphp

    <header class="head">
        <div>
            <h1>{{ $owner->name }}</h1>
            <p>هاتف: <span class="num" dir="ltr">{{ $owner->phone ?? '—' }}</span></p>
        </div>
        <div style="text-align:left">
            <p>الفاتورة: <span class="num">{{ $sale->invoice_number }}</span></p>
            <p>التاريخ: <span class="num">{{ $sale->sold_at?->format('Y-m-d') }}</span></p>
        </div>
    </header>

    <div class="title">كشف فاتورة دلال — سطور مصيد المالك</div>

    <div class="boxes">
        <div>
            <h3>الدلال</h3>
            <dl>
                <dt>الاسم</dt><dd>{{ $review->dalal?->name }}</dd>
                <dt>الجوال</dt><dd class="num" dir="ltr" style="text-align:right">{{ $review->dalal?->phone ?? '—' }}</dd>
                <dt>المنشأة</dt><dd>{{ $profile?->company_name ?? '—' }}</dd>
                <dt>الدكة</dt><dd>{{ $profile?->dakka_name ?? '—' }}{{ $profile?->dakka_number ? ' — '.$profile->dakka_number : '' }}</dd>
                <dt>السجل / الضريبي</dt><dd class="num">{{ $profile?->cr_number ?? '—' }} / {{ $profile?->vat_number ?? '—' }}</dd>
            </dl>
        </div>
        <div>
            <h3>الحال</h3>
            <dl>
                <dt>المشتري</dt><dd>{{ $sale->customer?->name ?? '—' }}</dd>
                <dt>الرحلات</dt><dd class="num">{{ implode('، ', $invoice['trips']) ?: '—' }}</dd>
                <dt>المراجعة</dt><dd>{{ $review->status_label }}{{ $review->reason ? ' — '.$review->reason : '' }}</dd>
                <dt>السداد</dt><dd>{{ $invoice['payment_label'] }} ({{ number_format($invoice['settled'], 2) }} ر.س)</dd>
            </dl>
        </div>
    </div>

    <table>
        <thead><tr><th>#</th><th>الصنف</th><th>الرحلة</th><th>القارب</th><th>الوزن (كجم)</th><th>سعر الكيلو</th><th>المبيعات</th><th>العمولة</th><th>الأجور</th><th>الصافي</th></tr></thead>
        <tbody>
            @foreach ($lines as $item)
                <tr>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td>{{ $item->species?->name_ar }}</td>
                    <td class="num">{{ $item->trip?->trip_number ?? '—' }}</td>
                    <td>{{ $item->trip?->boat?->name ?? '—' }}</td>
                    <td class="num">{{ number_format($item->weight_kg, 2) }}</td>
                    <td class="num">{{ number_format($item->price_per_kg, 2) }}</td>
                    <td class="num">{{ number_format($item->total, 2) }}</td>
                    <td class="num">{{ number_format($item->commission_amount, 2) }}</td>
                    <td class="num">{{ number_format($item->wage_amount, 2) }}</td>
                    <td class="num">{{ number_format($item->owner_net, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div><span>الوزن</span><span class="num">{{ number_format($invoice['weight_kg'], 2) }} كجم</span></div>
        <div><span>المبيعات</span><span class="num">{{ number_format($invoice['total'], 2) }} ر.س</span></div>
        <div><span>عمولة الدلال</span><span class="num">{{ number_format($invoice['commission'], 2) }} ر.س</span></div>
        <div><span>أجور العمالة</span><span class="num">{{ number_format($invoice['wage'], 2) }} ر.س</span></div>
        <div class="grand"><span>صافي المالك</span><span class="num">{{ number_format($invoice['owner_net'], 2) }} ر.س</span></div>
    </div>

    <div class="signs">
        <div><span></span>المالك</div>
        <div><span></span>الدلال</div>
        <div><span></span>الختم</div>
    </div>
@endsection
