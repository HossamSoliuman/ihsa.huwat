@extends('layouts.report')

@section('title', $meta['title'].' '.$from.' — '.$to)

@section('content')
    @php $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v); @endphp

    @include('panel.owner.reports.print.partials.masthead', ['subtitle' => 'Boat Profitability Report'])
    @include('panel.owner.reports.print.partials.info')
    @include('panel.owner.reports.print.partials.stats', ['items' => [
        ['label' => 'عدد القوارب', 'value' => count($rows)],
        ['label' => 'إجمالي المبيعات', 'value' => $totals['gross_sales'], 'money' => true],
        ['label' => 'المصروفات', 'value' => $totals['expenses'], 'money' => true],
        ['label' => 'صافي الربح', 'value' => $totals['net_profit'], 'money' => true, 'tone' => $totals['net_profit'] >= 0 ? 'good' : 'bad'],
    ]])

    <table class="report-table">
        <thead>
            <tr><th>القارب</th><th>إجمالي المبيعات</th><th>صافي المبيعات</th><th>المصروفات</th><th>صافي الربح</th><th>هامش الربح</th></tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['boat_name'] }}</td>
                    <td class="col-num">{{ $money($row['gross_sales']) }}</td>
                    <td class="col-num">{{ $money($row['net_sales']) }}</td>
                    <td class="col-num">{{ $money($row['expenses']) }}</td>
                    <td class="col-num {{ $row['net_profit'] >= 0 ? 'tx-good' : 'tx-bad' }}" style="font-weight:700">{{ $money($row['net_profit']) }}</td>
                    <td class="col-num"><bdi dir="ltr">{{ number_format($row['margin'], 1) }}%</bdi></td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted" style="padding:30px">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('panel.owner.reports.print.partials.summary', ['rows' => [
        ['label' => 'إجمالي المبيعات', 'value' => $totals['gross_sales'], 'money' => true],
        ['label' => 'صافي المبيعات', 'value' => $totals['net_sales'], 'money' => true],
        ['label' => 'المصروفات', 'value' => $totals['expenses'], 'money' => true],
        ['label' => 'صافي الربح', 'value' => $totals['net_profit'], 'money' => true, 'highlight' => true],
    ]])
@endsection
