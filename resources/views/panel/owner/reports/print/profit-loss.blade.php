@extends('layouts.report')

@section('title', $meta['title'].' '.$from.' — '.$to)

@section('content')
    @php
        $money = fn ($v, $parens = false) => \App\Services\Owner\OwnerReports::money($v, $parens);
        $net = (float) $f['net_profit'];
        $pct = fn ($value, $base) => $base > 0 ? number_format($value / $base * 100, 2) : '—';
        $revenueBase = (float) $f['gross_sales'];
        $expenseBase = (float) $f['total_expenses'];
        $percent = $f['owner_percent'] !== null ? ' ('.\App\Services\Owner\OwnerReports::percent($f['owner_percent']).')' : '';
    @endphp

    @include('panel.owner.reports.print.partials.masthead', ['subtitle' => "من \u{2066}{$from}\u{2069} إلى \u{2066}{$to}\u{2069} — القارب: ".($boat?->name ?? 'الكل')])

    @include('panel.owner.reports.print.partials.stats', ['items' => [
        ['label' => 'إجمالي المبيعات', 'value' => $f['gross_sales'], 'money' => true],
        ['label' => 'صافي الإيرادات', 'value' => $f['net_owner_revenue'], 'money' => true],
        ['label' => 'إجمالي المصاريف', 'value' => $f['total_expenses'], 'money' => true],
        ['label' => 'صافي الربح / الخسارة', 'value' => $net, 'money' => true],
        ['label' => 'الخسائر', 'value' => max(-$net, 0), 'money' => true],
        ['label' => 'حصة المالك', 'value' => $f['owner_share'], 'money' => true],
        ['label' => 'حصة البحارة', 'value' => $f['crew_share'], 'money' => true],
    ]])

    <table class="dual">
        <tr>
            <td class="dual-col" style="width:50%">
                <table class="report-table">
                    <thead>
                        <tr><th class="col-text" style="width:56%">البند</th><th class="col-num" style="width:26%">المبلغ</th><th style="width:18%">النسبة</th></tr>
                    </thead>
                    <tbody>
                        <tr><td class="col-text">إجمالي المبيعات</td><td class="col-num">{{ $money($f['gross_sales']) }}</td><td>{{ $pct($f['gross_sales'], $revenueBase) }}</td></tr>
                        <tr><td class="col-text">العمولة والعمالة</td><td class="col-num">{{ $money($f['commission_labor'], true) }}</td><td>{{ $pct($f['commission_labor'], $revenueBase) }}</td></tr>
                    </tbody>
                    <tfoot>
                        <tr><th class="col-text">صافي الإيرادات</th><th class="col-num">{{ $money($f['net_owner_revenue']) }}</th><th>{{ $pct($f['net_owner_revenue'], $revenueBase) }}</th></tr>
                    </tfoot>
                </table>

                <table class="report-table" style="margin-top:10px">
                    <tbody>
                        <tr><th class="col-text" style="width:62%">صافي الإيرادات</th><td class="col-num">{{ $money($f['net_owner_revenue']) }}</td></tr>
                        <tr><th class="col-text">إجمالي المصاريف</th><td class="col-num">{{ $money($f['total_expenses'], true) }}</td></tr>
                        <tr class="net-row"><th class="col-text">صافي الربح / الخسارة</th><td class="col-num">{{ $money($net) }}</td></tr>
                        <tr><th class="col-text">حصة المالك{{ $percent }}</th><td class="col-num">{{ $money($f['owner_share']) }}</td></tr>
                        <tr><th class="col-text">حصة البحارة</th><td class="col-num">{{ $money($f['crew_share']) }}</td></tr>
                    </tbody>
                </table>
            </td>
            <td class="dual-gap"></td>
            <td class="dual-col" style="width:50%">
                <table class="report-table">
                    <thead>
                        <tr><th class="col-text" style="width:56%">البند</th><th class="col-num" style="width:26%">المبلغ</th><th style="width:18%">النسبة</th></tr>
                    </thead>
                    <tbody>
                        <tr><td class="col-text">تكلفة المبيعات (تكلفة الرحلات)</td><td class="col-num">{{ $money($f['trip_expenses']) }}</td><td>{{ $pct($f['trip_expenses'], $expenseBase) }}</td></tr>
                        <tr><td class="col-text">المصروفات العمومية والإدارية</td><td class="col-num">{{ $money($f['general_expenses']) }}</td><td>{{ $pct($f['general_expenses'], $expenseBase) }}</td></tr>
                        <tr><td class="col-text">الإهلاك</td><td class="col-num">{{ $money($f['depreciation']) }}</td><td>{{ $pct($f['depreciation'], $expenseBase) }}</td></tr>
                    </tbody>
                    <tfoot>
                        <tr><th class="col-text">إجمالي المصاريف</th><th class="col-num">{{ $money($f['total_expenses']) }}</th><th>{{ $expenseBase > 0 ? '100.00' : '—' }}</th></tr>
                    </tfoot>
                </table>
            </td>
        </tr>
    </table>

    @if (count($f['crew_distribution']) > 0)
        <table class="report-table block">
            <thead>
                <tr>
                    <th class="col-text" style="width:34%">البحار</th>
                    <th style="width:18%">الصفة</th>
                    <th style="width:16%">نسبة خاصة</th>
                    <th style="width:14%">الأسهم</th>
                    <th class="col-num" style="width:18%">المستحق</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($f['crew_distribution'] as $member)
                    <tr>
                        <td class="col-text">{{ $member['name'] }}</td>
                        <td>{{ $member['role'] }}</td>
                        <td>{{ $member['custom_percent'] !== null ? number_format($member['custom_percent'], 2) : '-' }}</td>
                        <td>{{ $member['custom_percent'] !== null ? '-' : number_format($member['shares'], 2) }}</td>
                        <td class="col-num">{{ $money($member['due']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><th colspan="4" class="col-text">حصة البحارة</th><th class="col-num">{{ $money(collect($f['crew_distribution'])->sum('due')) }}</th></tr>
            </tfoot>
        </table>
    @endif

    @include('panel.owner.reports.print.partials.signatures', ['items' => ['المحاسب', 'المدير المالي', 'المدير العام']])
    @include('panel.owner.reports.print.partials.footer')
@endsection
