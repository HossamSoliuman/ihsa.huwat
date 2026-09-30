{{--
    قائمة الشهر (_month_summary_statement في hispa): الإيرادات، والإهلاك،
    والمصروفات التشغيلية والعمومية بفئاتها ومجموع كلٍّ، ثم صافي الربح، ثم
    توزيعه بين المالك والطاقم. سطر العمولة وصافي الإيراد يُظهران اقتطاع الدلال
    حتى تتطابق القائمة مع صافي الربح، و"فرق عن لقطة الإغلاق" بيعٌ تغيّر بعد
    إغلاق شهره (صافي الإيراد من الإغلاق والإجمالي من الفواتير).
--}}
@php
    $money = fn ($v, $parens = false) => \App\Services\Owner\OwnerReports::money($v, $parens);
    $percent = $f['owner_percent'];
    $pct = fn (float $v) => \App\Services\Owner\OwnerReports::percent($v);
@endphp

<table class="ms-statement">
    <thead>
        <tr><th colspan="2">الفترة: <bdi dir="ltr">{{ $from }}</bdi> — <bdi dir="ltr">{{ $to }}</bdi></th></tr>
    </thead>
    <tbody>
        <tr class="section"><td colspan="2">الإيرادات</td></tr>
        <tr class="line"><td>إجمالي المبيعات</td><td class="amount">{{ $money($f['gross_sales']) }}</td></tr>
        <tr class="line"><td class="indent">يُخصم: العمولة والعمالة</td><td class="amount tx-bad">{{ $money($f['commission_labor'], true) }}</td></tr>
        @if (abs($f['revenue_adjustment']) >= 0.01)
            <tr class="line"><td class="indent">فرق عن لقطة الإغلاق</td><td class="amount">{{ $money($f['revenue_adjustment']) }}</td></tr>
        @endif
        <tr class="subtotal light"><td>صافي إيراد المالك</td><td class="amount">{{ $money($f['net_owner_revenue']) }}</td></tr>

        <tr class="section"><td colspan="2">الإهلاك</td></tr>
        <tr class="line"><td class="indent">الإهلاك</td><td class="amount tx-bad">{{ $money($f['depreciation']) }}</td></tr>

        @foreach ([
            ['title' => 'المصروفات التشغيلية (الرحلات والصيانة)', 'rows' => $expenses['operating'], 'total_label' => 'إجمالي المصروفات التشغيلية', 'total' => $f['trip_expenses']],
            ['title' => 'المصروفات العمومية والإدارية', 'rows' => $expenses['general'], 'total_label' => 'إجمالي المصروفات العمومية', 'total' => $f['general_expenses']],
        ] as $block)
            <tr class="section"><td colspan="2">{{ $block['title'] }}</td></tr>
            @forelse ($block['rows'] as $row)
                <tr class="line"><td class="indent">{{ $row['category'] }}</td><td class="amount tx-bad">{{ $money($row['amount']) }}</td></tr>
            @empty
                <tr class="line faint"><td class="indent">لا توجد مصروفات</td><td class="amount">{{ $money(0) }}</td></tr>
            @endforelse
            <tr class="subtotal light"><td>{{ $block['total_label'] }}</td><td class="amount">{{ $money($block['total']) }}</td></tr>
        @endforeach

        <tr class="subtotal"><td>إجمالي المصروفات</td><td class="amount tx-bad">{{ $money($f['total_expenses'], true) }}</td></tr>
        <tr class="total"><td>صافي الربح / الخسارة</td><td class="amount {{ $f['net_profit'] < 0 ? 'tx-bad' : '' }}">{{ $money($f['net_profit']) }}</td></tr>
    </tbody>
</table>

<table class="ms-statement distribution">
    <thead>
        <tr><th colspan="2">توزيع الأرباح</th></tr>
    </thead>
    <tbody>
        <tr class="line"><td>حصة المالك @if ($percent !== null)({{ $pct($percent) }})@endif</td><td class="amount">{{ $money($f['owner_share']) }}</td></tr>
        <tr class="line"><td>حصة الطاقم @if ($percent !== null)({{ $pct(100 - $percent) }})@endif</td><td class="amount">{{ $money($f['crew_share']) }}</td></tr>
        <tr class="line"><td>عدد البحارة</td><td class="amount num">{{ number_format($f['crew_count']) }}</td></tr>
        <tr class="line"><td>نصيب البحار الواحد</td><td class="amount">{{ $money($f['per_fisherman']) }}</td></tr>
    </tbody>
</table>
