<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>hawat_invoice_{{ $sale->invoice_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    {{--
        فاتورة A4 مستقلة: لا قائمة ولا شريط — تُطبع أو تُحفظ PDF من نافذة
        الطباعة. زوايا حادة وخطوط شعرية كبقية اللوحة، بلا خلفيات.
    --}}
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Chakra Petch', 'Tajawal', sans-serif; color: #0f172a; background: #eef2f6; font-size: 12px; line-height: 1.7; }
        .sheet { max-width: 800px; margin: 24px auto; background: #fff; border-top: 4px solid #1d6fb8; padding: 32px 36px; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; padding-bottom: 16px; border-bottom: 1px dashed #cbd5e1; }
        .seller h1 { font-size: 17px; font-weight: 800; color: #1d6fb8; }
        .seller p { color: #475569; font-size: 11px; }
        .logo { max-height: 64px; max-width: 160px; }
        .title { text-align: center; font-size: 18px; font-weight: 800; color: #1d6fb8; margin: 18px 0; }
        .boxes { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f1f5f9; padding: 14px 16px; margin-bottom: 18px; }
        .boxes h3 { font-size: 12px; color: #1d6fb8; margin-bottom: 4px; }
        .boxes dl { display: grid; grid-template-columns: auto 1fr; gap: 2px 10px; font-size: 11.5px; }
        .boxes dt { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        thead th { background: #1d6fb8; color: #fff; font-weight: 600; font-size: 11px; padding: 8px 6px; text-align: right; }
        tbody td { padding: 8px 6px; border-bottom: 1px solid #e2e8f0; font-size: 11.5px; }
        .num { font-family: 'Chakra Petch', sans-serif; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .sci { direction: ltr; display: block; text-align: right; font-size: 10px; color: #64748b; }
        .totals { margin-inline-start: auto; width: 280px; background: #f1f5f9; padding: 10px 14px; }
        .totals div { display: flex; justify-content: space-between; padding: 2px 0; }
        .totals .grand { border-top: 1px solid #cbd5e1; margin-top: 4px; padding-top: 6px; font-weight: 800; color: #1d6fb8; font-size: 14px; }
        .signs { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; margin-top: 36px; font-size: 11px; color: #475569; }
        .signs span { display: block; border-bottom: 1px solid #94a3b8; height: 28px; margin-bottom: 4px; }
        .foot { margin-top: 28px; padding-top: 10px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; color: #64748b; font-size: 10.5px; }
        .thanks { text-align: center; color: #64748b; font-style: italic; margin-top: 18px; }
        .toolbar { max-width: 800px; margin: 16px auto 0; display: flex; gap: 8px; justify-content: flex-end; }
        .toolbar button { font-family: inherit; font-weight: 700; border: 1px solid #1d6fb8; background: #1d6fb8; color: #fff; padding: 8px 16px; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .sheet { margin: 0; max-width: none; padding: 0; border-top: 0; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">طباعة / حفظ PDF</button></div>

    <main class="sheet">
        <header class="head">
            <div class="seller">
                <h1>{{ $profile?->display_name ?: ($sale->seller?->name ?? 'تطبيق حوات للصيد البحري') }}</h1>
                @if ($profile?->dakka_name)<p>{{ $profile->dakka_name }}@if ($profile->dakka_number) — رقم <span class="num">{{ $profile->dakka_number }}</span>@endif</p>@endif
                @if ($profile?->address)<p>{{ $profile->address }}</p>@endif
                <p>هاتف: <span class="num" dir="ltr">{{ $profile?->company_phone ?: ($sale->seller?->phone ?? 'غير متوفر') }}</span></p>
                @if ($profile?->cr_number)<p>السجل التجاري: <span class="num">{{ $profile->cr_number }}</span></p>@endif
                @if ($profile?->vat_number)<p>الرقم الضريبي: <span class="num">{{ $profile->vat_number }}</span></p>@endif
                <p>تاريخ الفاتورة: <span class="num">{{ $sale->sold_at?->format('d-m-Y') }}</span></p>
            </div>
            <img class="logo" src="{{ $profile?->logo_url ?: asset('images/logo.png') }}" alt="الشعار">
        </header>

        <h2 class="title">فاتورة صيد بحري</h2>

        <section class="boxes">
            <div>
                <h3>معلومات العميل</h3>
                <dl>
                    <dt>الاسم</dt><dd>{{ $sale->customer?->name ?? 'عميل نقدي' }}</dd>
                    <dt>الهاتف</dt><dd class="num" dir="ltr" style="text-align:right">{{ $sale->customer?->phone ?? 'غير متوفر' }}</dd>
                    <dt>البريد</dt><dd dir="ltr" style="text-align:right">{{ $sale->customer?->email ?? 'غير متوفر' }}</dd>
                </dl>
            </div>
            <div>
                <h3>معلومات الفاتورة</h3>
                <dl>
                    <dt>رقم الفاتورة</dt><dd class="num">{{ $sale->invoice_number }}</dd>
                    <dt>طريقة الدفع</dt><dd>{{ $sale->paymentMethod?->name ?? '—' }}</dd>
                    <dt>حالة الفاتورة</dt><dd>{{ $sale->status }}@if ($sale->paymentStatus) — {{ $sale->paymentStatus->name }}@endif</dd>
                    @if ($sale->trip)<dt>الرحلة</dt><dd class="num">{{ $sale->trip->trip_number }}</dd>@endif
                </dl>
            </div>
        </section>

        <table>
            <thead><tr><th>#</th><th>نوع السمك</th><th>الوزن (كجم)</th><th>سعر الكيلو</th><th style="text-align:left">المجموع</th></tr></thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td class="num">{{ $loop->iteration }}</td>
                        <td>{{ $item->species?->name_ar }}@if ($item->species?->name_sci)<span class="sci">{{ $item->species->name_sci }}</span>@endif</td>
                        <td class="num">{{ number_format($item->weight_kg, 2) }}</td>
                        <td class="num">{{ number_format($item->price_per_kg, 2) }} ر.س</td>
                        <td class="num" style="text-align:left">{{ number_format($item->total, 2) }} ر.س</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div><span>المجموع الجزئي</span><span class="num">{{ number_format($sale->subtotal, 2) }} ر.س</span></div>
            <div><span>الخصم</span><span class="num">{{ number_format($sale->discount, 2) }} ر.س</span></div>
            <div><span>المدفوع</span><span class="num">{{ number_format($sale->paid_amount, 2) }} ر.س</span></div>
            @if ($sale->remaining > 0)<div><span>المتبقي</span><span class="num">{{ number_format($sale->remaining, 2) }} ر.س</span></div>@endif
            <div class="grand"><span>المبلغ الإجمالي</span><span class="num">{{ number_format($sale->total, 2) }} ر.س</span></div>
        </div>

        <div class="signs">
            <div><span></span>توقيع العميل</div>
            <div><span></span>توقيع المسؤول</div>
            <div><span></span>ختم المنشأة</div>
        </div>

        <p class="thanks">شكرًا لتعاملكم — نرحب بتواصلكم في أي وقت</p>

        <footer class="foot">
            <span>منصة حوات للصيد البحري</span>
            <span class="num">{{ now()->year }} ©</span>
        </footer>
    </main>
</body>
</html>
