@extends('layouts.sheet')

@section('title', 'hawat_month_closing_'.sprintf('%04d-%02d', $closing->year, $closing->month))
@section('orientation', 'landscape')
@section('width', '1050px')

@section('content')
    @php
        $t = $data['totals'];
        $g = $data['general'];
        $pct = fn ($v) => rtrim(rtrim(number_format($v, 2), '0'), '.');
    @endphp
    <header class="head">
        <div>
            <h1>{{ $owner->name }}</h1>
            <p>هاتف: <span class="num" dir="ltr">{{ $owner->phone ?? '—' }}</span></p>
        </div>
        <div style="text-align:left">
            <p>أُغلق: <b class="num">{{ $closing->closed_at->format('Y-m-d H:i') }}</b></p>
        </div>
    </header>

    <div class="title">إغلاق شهر {{ $closing->period_label }}</div>

    <table>
        <thead>
            <tr><th>القارب</th><th>الإيراد</th><th>المصروفات</th><th>الإهلاك</th><th>مؤجَّل داخل</th><th>المحمَّل</th><th>مؤجَّل للتالي</th><th>صافي الربح</th><th>نصيب المالك</th><th>نصيب الطاقم</th></tr>
        </thead>
        <tbody>
            @foreach ($data['boats'] as $boat)
                <tr>
                    <td>{{ $boat['boat_name'] }}</td>
                    <td class="num">{{ number_format($boat['revenue'], 2) }}</td>
                    <td class="num">{{ number_format($boat['expenses'], 2) }}</td>
                    <td class="num">{{ number_format($boat['depreciation_own'], 2) }}</td>
                    <td class="num">{{ number_format($boat['depreciation_brought_forward'], 2) }}</td>
                    <td class="num">{{ number_format($boat['depreciation_charged'], 2) }}</td>
                    <td class="num">{{ number_format($boat['depreciation_deferred'], 2) }}</td>
                    <td class="num">{{ number_format($boat['net_profit'], 2) }}</td>
                    <td class="num">{{ number_format($boat['owner_share'], 2) }} <span class="muted">({{ $pct($boat['owner_share_percent']) }}%)</span></td>
                    <td class="num">{{ number_format($boat['crew_pool'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>الإجمالي</td>
                <td class="num">{{ number_format($t['revenue'], 2) }}</td>
                <td class="num">{{ number_format($t['expenses'], 2) }}</td>
                <td class="num" colspan="2">{{ number_format($t['depreciation'], 2) }}</td>
                <td class="num">{{ number_format($t['depreciation_charged'], 2) }}</td>
                <td class="num">{{ number_format($t['depreciation_deferred'], 2) }}</td>
                <td class="num">{{ number_format($t['net_profit'], 2) }}</td>
                <td class="num">{{ number_format($t['owner_share'], 2) }}</td>
                <td class="num">{{ number_format($t['crew_pool'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="totals">
        <div><span>نصيب المالك من القوارب</span><span class="num">{{ number_format($t['owner_share'], 2) }}</span></div>
        <div><span>− مصروفات عامة</span><span class="num">{{ number_format($g['expenses'], 2) }}</span></div>
        <div><span>− إهلاك أصول عامة</span><span class="num">{{ number_format($g['depreciation'], 2) }}</span></div>
        <div class="grand"><span>صافي المالك</span><span class="num">{{ number_format($t['owner_net'], 2) }}</span></div>
    </div>

    @foreach ($data['boats'] as $boat)
        @continue($boat['dues'] === [])
        <p style="margin:14px 0 6px;font-weight:700">مستحقات طاقم {{ $boat['boat_name'] }}@if ($boat['payroll']) — <span class="num">{{ $boat['payroll']->payroll_number }}</span>@endif</p>
        <table>
            <thead><tr><th>الفرد</th><th>الأجر</th><th>المستحق</th><th>سلف</th><th>الصافي</th><th>السداد</th></tr></thead>
            <tbody>
                @foreach ($boat['dues'] as $due)
                    <tr>
                        <td>{{ $due['name'] }}@if ($due['is_captain']) <span class="muted">(كابتن)</span>@endif</td>
                        <td>{{ $due['pay'] }}</td>
                        <td class="num">{{ number_format($due['gross'], 2) }}</td>
                        <td class="num">{{ $due['advances'] === null ? '—' : number_format($due['advances'], 2) }}</td>
                        <td class="num">{{ $due['net'] === null ? '—' : number_format($due['net'], 2) }}</td>
                        <td>{{ $due['paid'] === null ? '—' : ($due['paid'] ? 'مسدَّد' : 'غير مسدَّد') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <p class="muted">صافي ربح القارب = الإيراد − المصروفات − الإهلاك المحمَّل (لا يُحمَّل من الإهلاك إلا ما يغطيه الربح والباقي يؤجَّل للشهر التالي). نصيب الطاقم = الصافي − نصيب المالك، ولا يقل عن صفر. ما لا قارب له يُنقص صافي المالك وحده.@if ($closing->notes) ملاحظة: {{ $closing->notes }}@endif</p>

    <div class="signs">
        <div><span></span>المحاسب</div>
        <div><span></span>المراجع</div>
        <div><span></span>المالك</div>
    </div>
@endsection
