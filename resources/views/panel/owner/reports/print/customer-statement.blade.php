@extends('layouts.report')

@section('title', 'كشف حساب العميل — '.$customer->name)

@section('content')
    @php
        $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v);
        $s = $statement['statistics'];
    @endphp

    @include('panel.owner.reports.print.partials.masthead', ['title' => 'كشف حساب العميل', 'subtitle' => 'Customer Statement'])

    <table class="info-bar">
        <tr>
            <td><span class="ib-label">بيانات العميل</span><span class="ib-value">{{ $customer->name }}</span></td>
            <td><span class="ib-label">الهاتف</span><span class="ib-value"><bdi dir="ltr">{{ $customer->phone ?: '—' }}</bdi></span></td>
            <td><span class="ib-label">البريد الإلكتروني</span><span class="ib-value">{{ $customer->email ?: '—' }}</span></td>
            @if ($customer->customerType)
                <td><span class="ib-label">النوع</span><span class="ib-value">{{ $customer->customerType->name }}</span></td>
            @endif
            <td><span class="ib-label">تاريخ التسجيل</span><span class="ib-value"><bdi dir="ltr">{{ $customer->created_at?->format('Y-m-d') ?? '—' }}</bdi></span></td>
        </tr>
    </table>

    @include('panel.owner.reports.print.partials.stats', ['items' => [
        ['label' => 'عدد الفواتير', 'value' => number_format($s['total_orders'])],
        ['label' => 'إجمالي المشتريات', 'value' => $s['total_purchases'], 'money' => true],
        ['label' => 'إجمالي المدفوع', 'value' => $s['total_paid'], 'money' => true],
        ['label' => 'إجمالي المتبقي', 'value' => $s['total_remaining'], 'money' => true, 'tone' => $s['total_remaining'] > 0 ? 'bad' : 'good'],
    ]])

    <div class="section-title">الفواتير والمشتريات</div>
    <table class="report-table block">
        <thead>
            <tr>
                <th style="width:6%">#</th>
                <th style="width:18%">رقم الفاتورة</th>
                <th style="width:13%">التاريخ</th>
                <th style="width:15%">وسيلة الدفع</th>
                <th style="width:14%">حالة الدفع</th>
                <th class="col-num" style="width:11%">الإجمالي</th>
                <th class="col-num" style="width:11%">المدفوع</th>
                <th class="col-num" style="width:12%">المتبقي</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($statement['rows'] as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><bdi dir="ltr">{{ $row['number'] }}</bdi></td>
                    <td><bdi dir="ltr">{{ $row['date'] }}</bdi></td>
                    <td>{{ $row['payment_method'] }}</td>
                    <td>{{ $row['payment_status'] }}</td>
                    <td class="col-num">{{ $money($row['total']) }}</td>
                    <td class="col-num">{{ $money($row['paid']) }}</td>
                    <td class="col-num">{{ $money($row['remaining']) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">لا توجد فواتير</td></tr>
            @endforelse
        </tbody>
        @if (count($statement['rows']))
            <tfoot>
                <tr>
                    <td colspan="5" class="col-text">الإجمالي</td>
                    <td class="col-num">{{ $money($s['total_purchases']) }}</td>
                    <td class="col-num">{{ $money($s['total_paid']) }}</td>
                    <td class="col-num">{{ $money($s['total_remaining']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    @include('panel.owner.reports.print.partials.footer')
@endsection
