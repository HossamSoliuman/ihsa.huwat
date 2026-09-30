@extends('layouts.report')

@section('title', $meta['title'].' '.$from.' — '.$to)

@section('content')
    @php $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v); @endphp

    @include('panel.owner.reports.print.partials.masthead', ['subtitle' => 'Trip Profitability Report'])
    @include('panel.owner.reports.print.partials.info')
    @include('panel.owner.reports.print.partials.stats', ['items' => [
        ['label' => 'عدد الرحلات', 'value' => count($rows)],
        ['label' => 'صافي المبيعات', 'value' => $totals['net_sales'], 'money' => true],
        ['label' => 'المصروفات', 'value' => $totals['expenses'], 'money' => true],
        ['label' => 'صافي الربح', 'value' => $totals['net_profit'], 'money' => true, 'tone' => $totals['net_profit'] >= 0 ? 'good' : 'bad'],
    ]])

    <table class="report-table">
        <thead>
            <tr><th style="width:15%">رقم الرحلة</th><th>القارب</th><th>الكابتن</th><th>تاريخ البدء</th><th>صافي المبيعات</th><th>المصروفات</th><th>صافي الربح</th><th>هامش الربح</th></tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="nowrap"><bdi dir="ltr">{{ $row['number'] }}</bdi></td>
                    <td>{{ $row['boat_name'] }}</td>
                    <td>{{ $row['captain_name'] }}</td>
                    <td><bdi dir="ltr">{{ $row['start_date'] ?? '—' }}</bdi></td>
                    <td class="col-num">{{ $money($row['net_sales']) }}</td>
                    <td class="col-num">{{ $money($row['expenses']) }}</td>
                    <td class="col-num {{ $row['net_profit'] >= 0 ? 'tx-good' : 'tx-bad' }}" style="font-weight:700">{{ $money($row['net_profit']) }}</td>
                    <td class="col-num"><bdi dir="ltr">{{ number_format($row['margin'], 1) }}%</bdi></td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted" style="padding:30px">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('panel.owner.reports.print.partials.summary', ['rows' => [
        ['label' => 'صافي المبيعات', 'value' => $totals['net_sales'], 'money' => true],
        ['label' => 'المصروفات', 'value' => $totals['expenses'], 'money' => true],
        ['label' => 'صافي الربح', 'value' => $totals['net_profit'], 'money' => true, 'highlight' => true],
    ]])

    <p class="note">ملاحظة: مصروفات الرحلة هي السندات المربوطة بها مباشرة، وصافي المبيعات كل ما بيع من مصيدها في أي تاريخ بعد عمولة الدلال وأجور العمالة.</p>
@endsection
