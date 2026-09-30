@extends('layouts.report')

@section('title', $meta['title'].($from || $to ? ' '.($from ?? '…').' — '.($to ?? '…') : ''))

@section('content')
    @php
        $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v);
        $s = $statistics;
    @endphp

    @include('panel.owner.reports.print.partials.masthead')

    @if ($from || $to || $status)
        <table class="info-bar">
            <tr>
                @if ($from)<td><span class="ib-label">من</span><span class="ib-value"><bdi dir="ltr">{{ $from }}</bdi></span></td>@endif
                @if ($to)<td><span class="ib-label">إلى</span><span class="ib-value"><bdi dir="ltr">{{ $to }}</bdi></span></td>@endif
                @if ($status)<td><span class="ib-label">الحالة</span><span class="ib-value">{{ $status }}</span></td>@endif
            </tr>
        </table>
    @endif

    <table class="info-bar">
        <tr>
            <td><span class="ib-label">إجمالي الرحلات</span><span class="ib-value">{{ $s['total_trips'] }}</span></td>
            <td><span class="ib-label">الرحلات المكتملة</span><span class="ib-value">{{ $s['completed_trips'] }}</span></td>
            <td><span class="ib-label">إجمالي المصيد</span><span class="ib-value">{{ number_format($s['total_weight'], 2) }} كجم</span></td>
            <td><span class="ib-label">صافي الربح</span><span class="ib-value">{{ $money($s['net_profit']) }}</span></td>
        </tr>
    </table>

    @if (count($rows) === 0)
        <p class="note">لا توجد بيانات للتصفية المختارة — حاول ضبط الفلاتر أو تحقق من وجود رحلات.</p>
    @else
        <table class="report-table block">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th style="width:13%">رقم الرحلة</th>
                    <th class="col-text" style="width:14%">الكابتن</th>
                    <th style="width:11%">تاريخ الانطلاق</th>
                    <th style="width:8%">المدة</th>
                    <th style="width:11%">الحالة</th>
                    <th style="width:12%">إجمالي المصيد</th>
                    <th class="col-num" style="width:13%">الإيرادات الكلية</th>
                    <th class="col-num" style="width:13%">صافي الربح</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="nowrap"><bdi dir="ltr">#{{ $row['number'] }}</bdi></td>
                        <td class="col-text">{{ $row['captain_name'] }}</td>
                        <td><bdi dir="ltr">{{ $row['departed'] ?? '-' }}</bdi></td>
                        <td>{{ $row['days'] ? $row['days'].' '.($row['days'] === 1 ? 'يوم' : 'أيام') : '-' }}</td>
                        <td>{{ $row['status'] }}</td>
                        <td>{{ number_format($row['weight'], 2) }} كجم</td>
                        <td class="col-num">{{ $money($row['gross_revenue']) }}</td>
                        <td class="col-num {{ $row['net_profit'] < 0 ? 'tx-bad' : '' }}">{{ $money($row['net_profit']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="col-text">الملخص المالي</td>
                    <td>{{ number_format($s['total_weight'], 2) }} كجم</td>
                    <td class="col-num">{{ $money($s['total_revenue']) }}</td>
                    <td class="col-num">{{ $money($s['net_profit']) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    @include('panel.owner.reports.print.partials.footer')
@endsection
