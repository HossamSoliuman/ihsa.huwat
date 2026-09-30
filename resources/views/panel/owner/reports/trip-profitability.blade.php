@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @php $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v); @endphp

    @include('panel.owner.reports.partials.head')
    @include('panel.owner.reports.partials.filter')

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>رقم الرحلة</th>
                    <th>القارب</th>
                    <th>الكابتن</th>
                    <th>تاريخ البدء</th>
                    <th>الحالة</th>
                    <th class="end">صافي المبيعات</th>
                    <th class="end">المصروفات</th>
                    <th class="end">صافي الربح</th>
                    <th class="end">هامش الربح</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td><a href="{{ route('panel.owner.trips.show', $row['trip_id']) }}"><bdi class="num" dir="ltr">{{ $row['number'] }}</bdi></a></td>
                        <td>{{ $row['boat_name'] }}</td>
                        <td>{{ $row['captain_name'] }}</td>
                        <td><bdi class="num" dir="ltr">{{ $row['start_date'] ?? '—' }}</bdi></td>
                        <td><span class="badge badge-muted">{{ $row['status_label'] }}</span></td>
                        <td class="end">{{ $money($row['net_sales']) }}</td>
                        <td class="end tx-bad">{{ $money($row['expenses']) }}</td>
                        <td class="end {{ $row['net_profit'] >= 0 ? 'tx-good' : 'tx-bad' }}" style="font-weight:700">{{ $money($row['net_profit']) }}</td>
                        <td class="end num"><bdi dir="ltr">{{ number_format($row['margin'], 1) }}%</bdi></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="empty">لا توجد بيانات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
            @if (count($rows))
                <tfoot>
                    <tr>
                        <td colspan="5">الإجمالي</td>
                        <td class="end">{{ $money($totals['net_sales']) }}</td>
                        <td class="end tx-bad">{{ $money($totals['expenses']) }}</td>
                        <td class="end {{ $totals['net_profit'] >= 0 ? 'tx-good' : 'tx-bad' }}">{{ $money($totals['net_profit']) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    <small class="report-note">ملاحظة: مصروفات الرحلة هي السندات المربوطة بها مباشرة، وصافي المبيعات كل ما بيع من مصيدها في أي تاريخ بعد عمولة الدلال وأجور العمالة.</small>
@endsection
