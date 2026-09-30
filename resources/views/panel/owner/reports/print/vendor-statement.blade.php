@extends('layouts.report')

@section('title', 'تقرير المورد — '.$vendor->name)

@section('content')
    @php $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v); @endphp

    @include('panel.owner.reports.print.partials.masthead', ['title' => 'تقرير المورد', 'subtitle' => 'Vendor Report'])

    <div class="info-section">
        <div class="info-label">معلومات التقرير</div>
        <p><strong>تاريخ الإنشاء:</strong> <bdi dir="ltr">{{ now()->format('Y-m-d H:i:s') }}</bdi></p>
        <p><strong>اسم المالك:</strong> {{ $owner->name }}</p>
        <p><strong>تاريخ التقرير:</strong> <bdi dir="ltr">{{ now()->format('Y-m-d') }}</bdi></p>
    </div>

    <table class="info-bar">
        <tr>
            <td><span class="ib-label">المورد</span><span class="ib-value">{{ $vendor->name }}</span></td>
            <td><span class="ib-label">الفلاتر المطبقة</span><span class="ib-value"><bdi dir="ltr">{{ $from || $to ? ($from ?? '…').' — '.($to ?? '…') : '-' }}</bdi></span></td>
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr><th style="width:6%">#</th><th>الفئة</th><th>الرحلة</th><th>المبلغ</th><th>الحالة</th><th>التاريخ</th></tr>
        </thead>
        <tbody>
            @forelse ($statement['rows'] as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row['category'] }}</td>
                    <td><bdi dir="ltr">{{ $row['trip'] }}</bdi></td>
                    <td>{{ $money($row['amount']) }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td><bdi dir="ltr">{{ $row['date'] }}</bdi></td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">لا توجد بيانات للتصفية المختارة</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('panel.owner.reports.print.partials.summary', ['rows' => [
        ['label' => 'الرصيد المستحق', 'value' => $statement['total_due'], 'money' => true],
        ['label' => 'إجمالي الإنفاق', 'value' => $statement['total_expenses'], 'money' => true, 'highlight' => true],
    ]])

    @include('panel.owner.reports.print.partials.footer')
@endsection
