@extends('layouts.report')

@section('title', $meta['title'].' '.$from.' — '.$to)

@section('content')
    @php
        $money = fn ($v, $parens = false) => \App\Services\Owner\OwnerReports::money($v, $parens);
        $net = (float) $f['net_profit'];
        $percent = $f['owner_percent'];
        $pct = fn (float $v) => \App\Services\Owner\OwnerReports::percent($v);
        $boatLabel = $boat?->name ?? 'كل القوارب';
        $indent = 'padding-inline-start:18px';
    @endphp

    @include('panel.owner.reports.print.partials.masthead', ['subtitle' => "من تاريخ \u{2066}{$from}\u{2069} إلى تاريخ \u{2066}{$to}\u{2069} — القارب: {$boatLabel}"])

    <div class="meta-row">
        <span class="meta-item"><span class="lbl">من تاريخ:</span><span class="val-box"><bdi dir="ltr">{{ $from }}</bdi></span></span>
        <span class="meta-item"><span class="lbl">إلى تاريخ:</span><span class="val-box"><bdi dir="ltr">{{ $to }}</bdi></span></span>
        <span class="meta-item"><span class="lbl">القارب:</span><span class="val-box">{{ $boatLabel }}</span></span>
    </div>

    @include('panel.owner.reports.print.partials.stats', ['items' => [
        ['label' => 'إجمالي المبيعات', 'value' => $f['gross_sales'], 'money' => true],
        ['label' => 'صافي إيراد المالك', 'value' => $f['net_owner_revenue'], 'money' => true],
        ['label' => 'إجمالي المصروفات', 'value' => $f['total_expenses'], 'money' => true],
        ['label' => 'صافي الربح / الخسارة', 'value' => $net, 'money' => true, 'tone' => $net < 0 ? 'bad' : null],
        ['label' => 'حصة المالك', 'value' => $f['owner_share'], 'money' => true],
        ['label' => 'حصة الطاقم', 'value' => $f['crew_share'], 'money' => true],
    ]])

    <table class="dual">
        <tr>
            <td class="dual-col" style="width:58%">
                <div class="section-bar">قائمة الأرباح والخسائر</div>
                <table class="report-table">
                    <thead>
                        <tr><th class="col-text" style="width:70%">البند</th><th class="col-num">المبلغ</th></tr>
                    </thead>
                    <tbody>
                        <tr><th class="col-text" colspan="2">الإيرادات</th></tr>
                        <tr><td class="col-text" style="{{ $indent }}">إجمالي المبيعات</td><td class="col-num">{{ $money($f['gross_sales']) }}</td></tr>
                        <tr><td class="col-text" style="{{ $indent }}">يُخصم: العمولة والعمالة</td><td class="col-num">{{ $money($f['commission_labor'], true) }}</td></tr>
                        @if (abs($f['revenue_adjustment']) >= 0.01)
                            <tr><td class="col-text" style="{{ $indent }}">فرق عن لقطة الإغلاق</td><td class="col-num">{{ $money($f['revenue_adjustment']) }}</td></tr>
                        @endif
                        <tr><th class="col-text">صافي إيراد المالك</th><th class="col-num">{{ $money($f['net_owner_revenue']) }}</th></tr>

                        @foreach ([
                            ['title' => 'المصروفات التشغيلية (الرحلات والصيانة)', 'rows' => $expenses['operating'], 'total_label' => 'إجمالي المصروفات التشغيلية', 'total' => $f['trip_expenses']],
                            ['title' => 'المصروفات العمومية والإدارية', 'rows' => $expenses['general'], 'total_label' => 'إجمالي المصروفات العمومية', 'total' => $f['general_expenses']],
                        ] as $block)
                            <tr><th class="col-text" colspan="2">{{ $block['title'] }}</th></tr>
                            @forelse ($block['rows'] as $row)
                                <tr><td class="col-text" style="{{ $indent }}">{{ $row['category'] }}</td><td class="col-num">{{ $money($row['amount'], true) }}</td></tr>
                            @empty
                                <tr><td class="col-text muted" style="{{ $indent }}">لا توجد مصروفات</td><td class="col-num muted">{{ $money(0) }}</td></tr>
                            @endforelse
                            <tr><th class="col-text">{{ $block['total_label'] }}</th><th class="col-num">{{ $money($block['total'], true) }}</th></tr>
                        @endforeach

                        <tr><th class="col-text" colspan="2">الإهلاك</th></tr>
                        <tr><td class="col-text" style="{{ $indent }}">الإهلاك</td><td class="col-num">{{ $money($f['depreciation'], true) }}</td></tr>
                    </tbody>
                    <tfoot>
                        <tr><th class="col-text">إجمالي المصروفات</th><th class="col-num">{{ $money($f['total_expenses'], true) }}</th></tr>
                        <tr class="net-row"><th class="col-text">صافي الربح / الخسارة</th><th class="col-num">{{ $money($net) }}</th></tr>
                    </tfoot>
                </table>
            </td>
            <td class="dual-gap"></td>
            <td class="dual-col" style="width:42%">
                <div class="section-bar">توزيع الأرباح</div>
                <table class="report-table">
                    <thead>
                        <tr><th class="col-text" style="width:62%">البند</th><th class="col-num">المبلغ</th></tr>
                    </thead>
                    <tbody>
                        <tr><td class="col-text">حصة المالك @if ($percent !== null)({{ $pct($percent) }})@endif</td><td class="col-num">{{ $money($f['owner_share']) }}</td></tr>
                        <tr><td class="col-text">حصة الطاقم @if ($percent !== null)({{ $pct(100 - $percent) }})@endif</td><td class="col-num">{{ $money($f['crew_share']) }}</td></tr>
                        <tr><td class="col-text">عدد البحارة</td><td class="col-num">{{ number_format($f['crew_count']) }}</td></tr>
                        <tr class="net-row"><th class="col-text">نصيب البحار الواحد</th><th class="col-num">{{ $money($f['per_fisherman']) }}</th></tr>
                    </tbody>
                </table>
                <table class="info-bar" style="margin-top:10px">
                    <tr>
                        <td><span class="ib-label">صافي إيراد المالك</span><span class="ib-value">{{ $money($f['net_owner_revenue']) }}</span></td>
                        <td><span class="ib-label">صافي الربح / الخسارة</span><span class="ib-value">{{ $money($net) }}</span></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($f['months_count'] > $f['closed_count'])
        <p class="note">الأشهر المقفلة في الفترة: {{ $f['closed_count'] }} من {{ $f['months_count'] }} — أرقام الأشهر المفتوحة معاينة لإغلاقها وتتغير حتى يُقفل الشهر.</p>
    @endif

    @include('panel.owner.reports.print.partials.signatures', ['items' => ['المحاسب', 'المدير المالي', 'المدير العام']])
    @include('panel.owner.reports.print.partials.footer', ['note' => 'جميع المبالغ بالريال السعودي'])
@endsection
