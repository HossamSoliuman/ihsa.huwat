@extends('layouts.report')

@section('title', $meta['title'].' — '.$year)
@section('orientation', 'landscape')
@section('page-width', '297mm')
@section('page-height', '210mm')

@section('content')
    @php
        $money = fn ($v, $parens = false) => \App\Services\Owner\OwnerReports::money($v, $parens);
        $months = \App\Services\Owner\OwnerReports::MONTHS;
        $t = $summary['totals'];
        $a = $analysis['analysis'];
        $trips = $analysis['trips'];
        $payroll = $analysis['payroll'];
        $sales = $analysis['sales'];
        $catch = $analysis['catch'];
        $crew = $analysis['crew_members'];

        $boatLabel = $boat?->name ?? 'كل القوارب';
        $verdict = $a['verdict'];
        $reportTitle = $meta['title'].' — '.$year;
        $phMeta = $boatLabel.' · '.$summary['closed_count'].'/12 · '.$verdict;
        $tone = fn (float $v) => $v >= 0 ? 'tx-good' : 'tx-bad';

        // الأرباع من أشهرها المُغلقة — تطابق مجموع السنة.
        $quarters = [];
        foreach (range(1, 4) as $q) {
            $rows = array_filter(array_map(fn ($m) => $summary['months'][$m], range($q * 3 - 2, $q * 3)));
            $qSales = array_sum(array_column($rows, 'gross_sales'));
            $qNet = array_sum(array_column($rows, 'net_profit'));
            $quarters[$q] = [
                'has' => $rows !== [],
                'sales' => $qSales,
                'expenses' => array_sum(array_column($rows, 'total_expenses')),
                'net' => $qNet,
                'margin' => $qSales > 0 ? round($qNet / $qSales * 100, 1) : 0.0,
            ];
        }

        $closed = array_filter($summary['months']);
        $trendMax = max([0.0, ...array_map(fn ($m) => abs((float) $m['net_profit']), $closed)]);
    @endphp

    {{-- ══════════ الصفحة 1 — نظرة عامة ══════════ --}}
    @include('panel.owner.reports.print.partials.masthead', ['title' => $reportTitle, 'subtitle' => 'ملخص السنة المالية من واقع الأشهر المقفلة: المبيعات والإيرادات والمصروفات والإهلاك شهرًا بشهر.'])

    <div class="meta-row">
        <span class="meta-item"><span class="lbl">السنة:</span><span class="val-box">{{ $year }}</span></span>
        <span class="meta-item"><span class="lbl">القارب:</span><span class="val-box">{{ $boatLabel }}</span></span>
        <span class="meta-item"><span class="lbl">الأشهر المقفلة:</span><span class="val-box"><bdi dir="ltr">{{ $summary['closed_count'] }} / 12</bdi></span></span>
        <span class="meta-item"><span class="lbl">الحالة:</span><span class="val-box">{{ $verdict }}</span></span>
    </div>

    @include('panel.owner.reports.print.partials.stats', ['landscape' => true, 'items' => [
        ['label' => 'إجمالي المبيعات', 'value' => $t['gross_sales'], 'money' => true],
        ['label' => 'صافي إيراد المالك', 'value' => $t['net_owner_revenue'], 'money' => true],
        ['label' => 'إجمالي المصروفات', 'value' => $t['total_expenses'], 'money' => true],
        ['label' => 'الإهلاك', 'value' => $t['depreciation'], 'money' => true],
        ['label' => 'صافي الربح', 'value' => $t['net_profit'], 'money' => true, 'tone' => $t['net_profit'] >= 0 ? 'good' : 'bad'],
        ['label' => 'هامش الربح', 'value' => number_format($a['margin'], 1).'%'],
    ]])

    @include('panel.owner.reports.print.partials.stats', ['landscape' => true, 'items' => [
        ['label' => 'متوسط الربح الشهري', 'value' => $a['avg_net'], 'money' => true],
        ['label' => 'أفضل شهر · '.($a['best'] ? $months[$a['best']['month']] : '—'), 'value' => $a['best']['net'] ?? 0, 'money' => true, 'tone' => 'good'],
        ['label' => 'أضعف شهر · '.($a['worst'] ? $months[$a['worst']['month']] : '—'), 'value' => $a['worst']['net'] ?? 0, 'money' => true, 'tone' => ($a['worst']['net'] ?? 0) < 0 ? 'bad' : null],
        ['label' => 'الأشهر الرابحة', 'value' => $a['profitable_months'].' / '.$a['closed_count']],
        ['label' => 'إجمالي حصة المالك', 'value' => $t['owner_share'], 'money' => true],
        ['label' => 'إجمالي حصة البحارة', 'value' => $t['crew_share'], 'money' => true],
    ]])

    <div class="section-bar">الأداء المالي السنوي</div>
    <table class="dual" style="margin-bottom:14px">
        <tr>
            <td class="dual-col" style="width:58%">
                <table class="report-table" style="margin-top:8px">
                    <thead><tr><th class="col-text">البند</th><th style="width:32%">القيمة</th></tr></thead>
                    <tbody>
                        <tr><td class="col-text">إجمالي المبيعات</td><td class="col-num">{{ $money($t['gross_sales']) }}</td></tr>
                        <tr><td class="col-text">ناقص: العمولة والعمالة</td><td class="col-num">{{ $money($t['gross_sales'] - $t['net_owner_revenue'], true) }}</td></tr>
                        <tr><td class="col-text">ناقص: مصروفات الرحلات</td><td class="col-num">{{ $money($t['trip_expenses'], true) }}</td></tr>
                        <tr><td class="col-text">ناقص: مصروفات عمومية</td><td class="col-num">{{ $money($t['general_expenses'], true) }}</td></tr>
                        <tr><td class="col-text">ناقص: الإهلاك</td><td class="col-num">{{ $money($t['depreciation'], true) }}</td></tr>
                        <tr class="net-row"><td class="col-text">صافي الربح</td><td class="col-num">{{ $money($t['net_profit']) }}</td></tr>
                        <tr><td class="col-text">حصة المالك</td><td class="col-num">{{ $money($t['owner_share']) }}</td></tr>
                        <tr><td class="col-text">حصة البحارة</td><td class="col-num">{{ $money($t['crew_share']) }}</td></tr>
                    </tbody>
                </table>
            </td>
            <td class="dual-gap"></td>
            <td class="dual-col">
                <table class="report-table info-box" style="margin-top:8px">
                    <thead><tr><th colspan="2">أجور البحارة</th></tr></thead>
                    <tbody>
                        <tr><td class="col-text">عدد البحارة</td><td class="col-num">{{ $payroll['crew_count'] }}</td></tr>
                        <tr><td class="col-text">إجمالي حصة البحارة</td><td class="col-num">{{ $money($payroll['crew_pool']) }}</td></tr>
                        <tr><td class="col-text">إجمالي حصة المالك</td><td class="col-num">{{ $money($payroll['owner_share']) }}</td></tr>
                        <tr><td class="col-text">إجمالي السلف</td><td class="col-num">{{ $money($payroll['advances']) }}</td></tr>
                        <tr><td class="col-text">إجمالي المدفوع</td><td class="col-num">{{ $money($payroll['paid']) }}</td></tr>
                        <tr class="net-row"><td class="col-text">صافي المستحق</td><td class="col-num">{{ $money($payroll['remaining']) }}</td></tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    @include('panel.owner.reports.print.partials.amount-words', ['amount' => $t['net_profit']])

    {{-- ══════════ الصفحة 2 — تفصيل الأشهر ══════════ --}}
    <div class="page-break">
        <div class="page-head">
            <span class="ph-title">تفصيل الأشهر</span>
            <span class="ph-meta">{{ $reportTitle }} · {{ $phMeta }}</span>
        </div>

        <table class="report-table" style="margin-bottom:14px">
            <thead>
                <tr>
                    <th class="col-text" style="width:8%">الشهر</th>
                    <th style="width:7%">الحالة</th>
                    <th>المبيعات</th>
                    <th>الإيرادات</th>
                    <th>مصروفات الرحلات</th>
                    <th>مصروفات عمومية</th>
                    <th>الإهلاك</th>
                    <th>الإهلاك المُرحّل</th>
                    <th>إجمالي المصروفات</th>
                    <th>صافي الربح</th>
                    <th>حصة البحارة</th>
                    <th>حصة المالك</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summary['months'] as $m => $row)
                    <tr>
                        <td class="col-text">{{ $months[$m] }}</td>
                        @if ($row)
                            <td>مقفل</td>
                            <td class="col-num">{{ $money($row['gross_sales']) }}</td>
                            <td class="col-num">{{ $money($row['net_owner_revenue']) }}</td>
                            <td class="col-num">{{ $money($row['trip_expenses']) }}</td>
                            <td class="col-num">{{ $money($row['general_expenses']) }}</td>
                            <td class="col-num">{{ $money($row['depreciation']) }}</td>
                            <td class="col-num">{{ $money($row['depreciation_deferred']) }}</td>
                            <td class="col-num">{{ $money($row['total_expenses']) }}</td>
                            <td class="col-num {{ $tone($row['net_profit']) }}">{{ $money($row['net_profit']) }}</td>
                            <td class="col-num">{{ $money($row['crew_share']) }}</td>
                            <td class="col-num">{{ $money($row['owner_share']) }}</td>
                        @else
                            <td class="muted">لم يُقفل</td>
                            @for ($i = 0; $i < 10; $i++)<td class="col-num muted">—</td>@endfor
                        @endif
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="net-row">
                    <td class="col-text" colspan="2">إجمالي السنة</td>
                    <td class="col-num">{{ $money($t['gross_sales']) }}</td>
                    <td class="col-num">{{ $money($t['net_owner_revenue']) }}</td>
                    <td class="col-num">{{ $money($t['trip_expenses']) }}</td>
                    <td class="col-num">{{ $money($t['general_expenses']) }}</td>
                    <td class="col-num">{{ $money($t['depreciation']) }}</td>
                    <td class="col-num">{{ $money($t['depreciation_deferred']) }}</td>
                    <td class="col-num">{{ $money($t['total_expenses']) }}</td>
                    <td class="col-num">{{ $money($t['net_profit']) }}</td>
                    <td class="col-num">{{ $money($t['crew_share']) }}</td>
                    <td class="col-num">{{ $money($t['owner_share']) }}</td>
                </tr>
            </tfoot>
        </table>

        <table class="dual">
            <tr>
                <td class="dual-col" style="width:46%">
                    <div class="section-title" style="margin-top:4px">الأداء الربع سنوي</div>
                    <table class="report-table">
                        <thead>
                            <tr><th class="col-text" style="width:34%">الربع</th><th>المبيعات</th><th>المصروفات</th><th>صافي الربح</th><th style="width:13%">الهامش</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($quarters as $q => $qd)
                                <tr>
                                    <td class="col-text">{{ \App\Services\Owner\OwnerReports::QUARTERS[$q] }}</td>
                                    @if ($qd['has'])
                                        <td class="col-num">{{ $money($qd['sales']) }}</td>
                                        <td class="col-num">{{ $money($qd['expenses']) }}</td>
                                        <td class="col-num {{ $tone($qd['net']) }}">{{ $money($qd['net']) }}</td>
                                        <td class="col-num"><bdi dir="ltr">{{ number_format($qd['margin'], 1) }}%</bdi></td>
                                    @else
                                        @for ($i = 0; $i < 4; $i++)<td class="col-num muted">—</td>@endfor
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="net-row">
                                <td class="col-text">إجمالي السنة</td>
                                <td class="col-num">{{ $money($t['gross_sales']) }}</td>
                                <td class="col-num">{{ $money($t['total_expenses']) }}</td>
                                <td class="col-num">{{ $money($t['net_profit']) }}</td>
                                <td class="col-num"><bdi dir="ltr">{{ number_format($a['margin'], 1) }}%</bdi></td>
                            </tr>
                        </tfoot>
                    </table>
                </td>
                <td class="dual-gap"></td>
                <td class="dual-col">
                    <div class="section-title" style="margin-top:4px">اتجاه صافي الربح الشهري</div>
                    <table class="report-table">
                        <thead>
                            <tr><th class="col-text" style="width:20%">الشهر</th><th class="col-text">صافي الربح</th><th style="width:22%">القيمة</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($closed as $m => $row)
                                @php $net = (float) $row['net_profit']; @endphp
                                <tr>
                                    <td class="col-text">{{ $months[$m] }}</td>
                                    <td style="padding:5px 6px">
                                        <div class="bar-track"><div class="bar-fill" style="width:{{ $trendMax > 0 ? round(abs($net) / $trendMax * 100) : 0 }}%;background:{{ $net >= 0 ? '#198754' : '#dc3545' }}"></div></div>
                                    </td>
                                    <td class="col-num {{ $tone($net) }}">{{ $money($net) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="muted">لا توجد بيانات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- ══════════ الصفحة 3 — المبيعات والإنتاج ══════════ --}}
    <div class="page-break">
        <div class="page-head">
            <span class="ph-title">تحليل المبيعات</span>
            <span class="ph-meta">{{ $reportTitle }} · {{ $phMeta }}</span>
        </div>

        @include('panel.owner.reports.print.partials.stats', ['landscape' => true, 'items' => [
            ['label' => 'إجمالي المبيعات', 'value' => $sales['totals']['gross'], 'money' => true],
            ['label' => 'صافي مبلغ المالك', 'value' => $sales['totals']['net_owner'], 'money' => true],
            ['label' => 'عدد الفواتير', 'value' => number_format($sales['totals']['invoices'])],
            ['label' => 'متوسط الفاتورة', 'value' => $sales['totals']['avg_invoice'], 'money' => true],
        ]])

        <table class="dual" style="margin-bottom:14px">
            <tr>
                <td class="dual-col" style="width:52%">
                    <div class="section-title" style="margin-top:4px">أعلى الأصناف مبيعًا</div>
                    <table class="report-table">
                        <thead><tr><th class="col-text">الصنف</th><th style="width:26%">الوزن</th><th style="width:30%">الإيراد</th></tr></thead>
                        <tbody>
                            @forelse ($sales['by_fish'] as $fish)
                                <tr>
                                    <td class="col-text">{{ $fish['name'] }}</td>
                                    <td class="col-num">{{ number_format($fish['weight'], 2) }} كجم</td>
                                    <td class="col-num">{{ $money($fish['total']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="muted">لا توجد بيانات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
                <td class="dual-gap"></td>
                <td class="dual-col">
                    <div class="section-title" style="margin-top:4px">أكبر العملاء</div>
                    <table class="report-table">
                        <thead><tr><th class="col-text">العميل</th><th style="width:24%">الفواتير</th><th style="width:30%">الإجمالي</th></tr></thead>
                        <tbody>
                            @forelse ($sales['top_customers'] as $customer)
                                <tr>
                                    <td class="col-text">{{ $customer['name'] }}</td>
                                    <td class="col-num">{{ number_format($customer['invoices']) }}</td>
                                    <td class="col-num">{{ $money($customer['total']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="muted">لا توجد بيانات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        @if (! empty($sales['by_boat']))
            <div class="section-title">المبيعات حسب القارب</div>
            <table class="report-table" style="margin-bottom:14px">
                <thead><tr><th class="col-text">القارب</th><th style="width:20%">الفواتير</th><th style="width:24%">الإجمالي</th></tr></thead>
                <tbody>
                    @foreach ($sales['by_boat'] as $row)
                        <tr>
                            <td class="col-text">{{ $row['name'] }}</td>
                            <td class="col-num">{{ number_format($row['invoices']) }}</td>
                            <td class="col-num">{{ $money($row['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="section-bar">إنتاج الصيد</div>
        <table class="info-bar" style="margin-top:8px">
            <tr>
                <td><span class="ib-label">رحلات بها صيد</span><span class="ib-value">{{ number_format($catch['trips_with_catch']) }}</span></td>
                <td><span class="ib-label">إجمالي الوزن</span><span class="ib-value">{{ number_format($catch['total_weight'], 2) }} كجم</span></td>
                <td><span class="ib-label">إجمالي القيمة</span><span class="ib-value">{{ $money($catch['total_amount']) }}</span></td>
            </tr>
        </table>

        <div class="section-title">أكثر الأصناف صيدًا</div>
        <table class="report-table">
            <thead><tr><th class="col-text">الصنف</th><th style="width:26%">الوزن</th><th style="width:26%">القيمة</th></tr></thead>
            <tbody>
                @forelse ($catch['by_species'] as $species)
                    <tr>
                        <td class="col-text">{{ $species['name'] }}</td>
                        <td class="col-num">{{ number_format($species['weight'], 2) }} كجم</td>
                        <td class="col-num">{{ $money($species['total']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">لا توجد بيانات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ══════════ الصفحة 4 — المصروفات والطاقم والتحليل ══════════ --}}
    <div class="page-break">
        <div class="page-head">
            <span class="ph-title">التحليل السنوي</span>
            <span class="ph-meta">{{ $reportTitle }} · {{ $phMeta }}</span>
        </div>

        <table class="dual" style="margin-bottom:14px">
            <tr>
                <td class="dual-col" style="width:52%">
                    <table class="report-table info-box">
                        <thead><tr><th class="col-text">المصروفات حسب البند</th><th style="width:40%">إجمالي المصروفات</th></tr></thead>
                        <tbody>
                            @forelse ($analysis['expenses_by_category'] as $row)
                                <tr><td class="col-text">{{ $row['name'] }}</td><td class="col-num">{{ $money($row['total']) }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="muted">لا توجد أشهر مقفلة في هذه السنة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
                <td class="dual-gap"></td>
                <td class="dual-col">
                    <table class="report-table info-box">
                        <thead><tr><th class="col-text">المصروفات حسب النوع</th><th style="width:40%">إجمالي المصروفات</th></tr></thead>
                        <tbody>
                            @forelse ($analysis['expenses_by_type'] as $row)
                                <tr><td class="col-text">{{ $row['label'] }}</td><td class="col-num">{{ $money($row['total']) }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="muted">لا توجد أشهر مقفلة في هذه السنة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <div class="section-bar">أرباح البحارة (سنوي)</div>
        <table class="report-table" style="margin-top:8px;margin-bottom:14px">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th class="col-text" style="width:24%">الاسم</th>
                    <th>الصفة</th>
                    <th style="width:9%">الأشهر</th>
                    <th>المستحق</th>
                    <th>السلف</th>
                    <th>المدفوع</th>
                    <th>صافي المستحق</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($crew as $member)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="col-text">{{ $member['name'] }}</td>
                        <td>{{ $member['role'] }}</td>
                        <td class="col-num">{{ $member['months'] }}</td>
                        <td class="col-num">{{ $money($member['earned']) }}</td>
                        <td class="col-num">{{ $money($member['advances']) }}</td>
                        <td class="col-num">{{ $money($member['paid']) }}</td>
                        <td class="col-num">{{ $money($member['remaining']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">لا توجد بيانات</td></tr>
                @endforelse
            </tbody>
            @if (! empty($crew))
                <tfoot>
                    <tr class="net-row">
                        <td class="col-text" colspan="4">إجمالي السنة</td>
                        <td class="col-num">{{ $money(array_sum(array_column($crew, 'earned'))) }}</td>
                        <td class="col-num">{{ $money($payroll['advances']) }}</td>
                        <td class="col-num">{{ $money($payroll['paid']) }}</td>
                        <td class="col-num">{{ $money($payroll['remaining']) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>

        <div class="section-bar">التحليل السنوي</div>
        <table class="dual" style="margin-top:8px;margin-bottom:14px">
            <tr>
                <td class="dual-col" style="width:24%">
                    <table class="report-table info-box">
                        <thead><tr><th colspan="2">نشاط الرحلات</th></tr></thead>
                        <tbody>
                            <tr><td class="col-text">إجمالي الرحلات</td><td class="col-num">{{ $trips['total'] }}</td></tr>
                            <tr><td class="col-text">رحلات مُباعة</td><td class="col-num">{{ $trips['sold'] }}</td></tr>
                            <tr><td class="col-text">رحلات قائمة</td><td class="col-num">{{ $trips['active'] }}</td></tr>
                            <tr><td class="col-text">رحلات ملغاة</td><td class="col-num">{{ $trips['cancelled'] }}</td></tr>
                        </tbody>
                    </table>
                </td>
                <td class="dual-gap"></td>
                <td class="dual-col">
                    <div class="section-title" style="margin-top:4px">أهم المؤشرات</div>
                    <ul class="cf-notes">
                        @foreach ($a['insights'] as $insight)<li>{{ $insight }}</li>@endforeach
                    </ul>
                </td>
                <td class="dual-gap"></td>
                <td class="dual-col">
                    <div class="section-title" style="margin-top:4px">التوصيات</div>
                    <ul class="cf-notes">
                        @foreach ($a['recommendations'] as $recommendation)<li>{{ $recommendation }}</li>@endforeach
                    </ul>
                </td>
            </tr>
        </table>

        @if ($t['depreciation_deferred'] > 0)
            <p style="font-size:8.5pt;color:#555;margin:0 0 10px">يُرحّل الإهلاك غير المُحتسب في الأشهر ذات العجز إلى الأشهر التالية.</p>
        @endif

        @include('panel.owner.reports.print.partials.signatures', ['items' => ['أعدّه', 'راجعه', 'المالك']])
    </div>

    @include('panel.owner.reports.print.partials.footer', ['note' => 'جميع المبالغ بالريال السعودي · تُحتسب الأشهر المقفلة فقط'])
@endsection
