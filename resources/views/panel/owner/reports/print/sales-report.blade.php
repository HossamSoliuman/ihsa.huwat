@extends('layouts.report')

@section('title', 'تقرير المبيعات '.$from.' — '.$to)

@section('content')
    @php
        $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v);
        $s = $statistics;
    @endphp

    @include('panel.owner.reports.print.partials.masthead', ['title' => 'تقرير المبيعات'])

    <table class="info-bar">
        <tr>
            <td><span class="ib-label">من تاريخ</span><span class="ib-value"><bdi dir="ltr">{{ $from }}</bdi></span></td>
            <td><span class="ib-label">إلى تاريخ</span><span class="ib-value"><bdi dir="ltr">{{ $to }}</bdi></span></td>
            <td><span class="ib-label">تصفية الحالة</span><span class="ib-value">{{ $status ? $statuses[$status] : 'الكل' }}</span></td>
            <td><span class="ib-label">تاريخ الإنشاء</span><span class="ib-value"><bdi dir="ltr">{{ now()->format('Y-m-d H:i') }}</bdi></span></td>
        </tr>
    </table>

    @include('panel.owner.reports.print.partials.stats', ['items' => [
        ['label' => 'إجمالي المبيعات', 'value' => $s['total_sales']],
        ['label' => 'إجمالي الإيرادات', 'value' => $s['total_revenue'], 'money' => true],
        ['label' => 'إجمالي الوزن', 'value' => number_format($s['total_weight'], 2).' كجم'],
        ['label' => 'صافي مبلغ المالك', 'value' => $s['net_owner'], 'money' => true, 'tone' => 'good'],
    ]])

    <table class="report-table">
        <thead>
            <tr><th style="width:5%">#</th><th>رقم الفاتورة</th><th>الحالة</th><th>الزبون</th><th>وسيلة الدفع</th><th>الوزن</th><th>السعر الإجمالي</th><th>التاريخ</th></tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="nowrap"><bdi dir="ltr">{{ $row['number'] }}</bdi></td>
                    <td class="{{ $row['completed'] ? 'tx-good' : '' }}" style="font-weight:700">{{ $row['completed'] ? 'مكتملة' : 'جارية' }}</td>
                    <td>{{ $row['customer'] }}</td>
                    <td>{{ $row['payment_method'] }}</td>
                    <td>{{ number_format($row['weight'], 2) }} كجم</td>
                    <td>{{ $money($row['total']) }}</td>
                    <td><bdi dir="ltr">{{ $row['date'] }}</bdi></td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted" style="padding:30px">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('panel.owner.reports.print.partials.footer')
@endsection
