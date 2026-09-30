@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @php $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v); @endphp

    @include('panel.owner.reports.partials.head')
    @include('panel.owner.reports.partials.filter', ['showBoat' => false])

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>القارب</th>
                    <th class="end">إجمالي المبيعات</th>
                    <th class="end">صافي المبيعات</th>
                    <th class="end">المصروفات</th>
                    <th class="end">صافي الربح</th>
                    <th class="end">هامش الربح</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row['boat_name'] }}</td>
                        <td class="end">{{ $money($row['gross_sales']) }}</td>
                        <td class="end">{{ $money($row['net_sales']) }}</td>
                        <td class="end tx-bad">{{ $money($row['expenses']) }}</td>
                        <td class="end {{ $row['net_profit'] >= 0 ? 'tx-good' : 'tx-bad' }}" style="font-weight:700">{{ $money($row['net_profit']) }}</td>
                        <td class="end num"><bdi dir="ltr">{{ number_format($row['margin'], 1) }}%</bdi></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">لا توجد بيانات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
            @if (count($rows))
                <tfoot>
                    <tr>
                        <td>الإجمالي</td>
                        <td class="end">{{ $money($totals['gross_sales']) }}</td>
                        <td class="end">{{ $money($totals['net_sales']) }}</td>
                        <td class="end tx-bad">{{ $money($totals['expenses']) }}</td>
                        <td class="end {{ $totals['net_profit'] >= 0 ? 'tx-good' : 'tx-bad' }}">{{ $money($totals['net_profit']) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    <small class="report-note">الفترة أشهر كاملة: المبيعات من فواتير الفترة، وصافيها والمصروفات (السندات والإهلاك المحمَّل) وصافي الربح من إغلاق كل شهر.</small>
@endsection
