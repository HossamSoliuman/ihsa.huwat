@extends('layouts.sheet')

@section('title', 'hawat_dalal_statement_'.$dalal->id)
@section('orientation', 'portrait')

@section('content')
    <header class="head">
        <div>
            <h1>{{ $owner->name }}</h1>
            <p>هاتف: <span class="num" dir="ltr">{{ $owner->phone ?? '—' }}</span></p>
        </div>
        <div style="text-align:left">
            <p>التاريخ: <span class="num">{{ now()->format('Y-m-d') }}</span></p>
            <p>الفترة: <span class="num">{{ $from ?? 'البداية' }} — {{ $to ?? 'اليوم' }}</span></p>
        </div>
    </header>

    <div class="title">كشف حساب دلال</div>

    <div class="boxes">
        <div>
            <h3>الدلال</h3>
            <dl>
                <dt>الاسم</dt><dd>{{ $dalal->name }}</dd>
                <dt>الجوال</dt><dd class="num" dir="ltr" style="text-align:right">{{ $dalal->phone ?? '—' }}</dd>
                <dt>المنشأة</dt><dd>{{ $dalal->dalalProfile?->company_name ?? '—' }}</dd>
                <dt>الدكة</dt><dd>{{ $dalal->dalalProfile?->dakka_name ?? '—' }}</dd>
            </dl>
        </div>
        <div>
            <h3>الملخص (كل الفترات)</h3>
            <dl>
                <dt>المبيعات</dt><dd class="num">{{ number_format($account['sales_total'] ?? 0, 2) }} ر.س</dd>
                <dt>العمولة والأجور</dt><dd class="num">{{ number_format($account['deductions'] ?? 0, 2) }} ر.س</dd>
                <dt>صافي المالك</dt><dd class="num">{{ number_format($account['owner_net'] ?? 0, 2) }} ر.س</dd>
                <dt>المستلم</dt><dd class="num">{{ number_format($account['paid'] ?? 0, 2) }} ر.س</dd>
                <dt>المستحق</dt><dd class="num" style="font-weight:800">{{ number_format($account['balance'] ?? 0, 2) }} ر.س</dd>
            </dl>
        </div>
    </div>

    <table>
        <thead><tr><th>التاريخ</th><th>البيان</th><th>التفاصيل</th><th>المرجع</th><th>صافي للمالك</th><th>مستلم</th><th>الرصيد</th></tr></thead>
        <tbody>
            @if ($from)
                <tr>
                    <td class="num">{{ $from }}</td>
                    <td colspan="5">رصيد افتتاحي</td>
                    <td class="num">{{ number_format($statement['opening'], 2) }}</td>
                </tr>
            @endif
            @forelse ($statement['entries'] as $entry)
                <tr>
                    <td class="num">{{ $entry['date']?->format('Y-m-d') }}</td>
                    <td>{{ $entry['label'] }}@if ($entry['number']) <bdi class="num" dir="ltr">{{ $entry['number'] }}</bdi>@endif</td>
                    <td>{{ $entry['details'] ?: '—' }}</td>
                    <td class="num">{{ $entry['reference'] ?? '—' }}</td>
                    <td class="num">{{ $entry['net'] ? number_format($entry['net'], 2) : '—' }}</td>
                    <td class="num">{{ $entry['paid'] ? number_format($entry['paid'], 2) : '—' }}</td>
                    <td class="num">{{ number_format($entry['balance'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center">لا حركات</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">مجموع الفترة</td>
                <td class="num">{{ number_format($statement['totals']['net'], 2) }}</td>
                <td class="num">{{ number_format($statement['totals']['paid'], 2) }}</td>
                <td class="num">{{ number_format($statement['totals']['closing'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="signs">
        <div><span></span>المالك</div>
        <div><span></span>الدلال</div>
        <div><span></span>الختم</div>
    </div>
@endsection
