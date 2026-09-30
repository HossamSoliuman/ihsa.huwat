@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @php $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v); @endphp

    @include('panel.owner.reports.partials.head')
    @include('panel.owner.reports.partials.statement-filter', [
        'entityParam' => 'customer_id',
        'entityLabel' => 'العميل',
        'placeholder' => 'اختر العميل',
        'selectedId' => $customer?->id,
        'groups' => [['label' => null, 'items' => $customers]],
    ])

    @if (! $customer)
        <div class="report-prompt">اختر من القائمة أعلاه لعرض كشف الحساب.</div>
    @else
        @php $s = $statement['statistics']; @endphp
        <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
            @include('partials.stat-card', ['label' => 'عدد الفواتير', 'value' => number_format($s['total_orders']), 'icon' => 'file-text', 'tone' => 'primary'])
            @include('partials.stat-card', ['label' => 'إجمالي المشتريات', 'value' => number_format($s['total_purchases'], 2), 'unit' => 'ر.س', 'icon' => 'shopping-cart', 'tone' => 'muted'])
            @include('partials.stat-card', ['label' => 'إجمالي المدفوع', 'value' => number_format($s['total_paid'], 2), 'unit' => 'ر.س', 'icon' => 'check-circle', 'tone' => 'success'])
            @include('partials.stat-card', ['label' => 'إجمالي المتبقي', 'value' => number_format($s['total_remaining'], 2), 'unit' => 'ر.س', 'icon' => 'alert-triangle', 'tone' => $s['total_remaining'] > 0 ? 'danger' : 'success'])
        </div>

        <div class="card">
            <div class="card-title" style="margin-bottom:.75rem">{{ $customer->name }}</div>
            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم الفاتورة</th>
                            <th>التاريخ</th>
                            <th>وسيلة الدفع</th>
                            <th>حالة الدفع</th>
                            <th class="end">الإجمالي</th>
                            <th class="end">المدفوع</th>
                            <th class="end">المتبقي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($statement['rows'] as $row)
                            <tr>
                                <td class="num">{{ $loop->iteration }}</td>
                                <td><bdi class="num" dir="ltr">{{ $row['number'] }}</bdi></td>
                                <td><bdi class="num" dir="ltr">{{ $row['date'] }}</bdi></td>
                                <td>{{ $row['payment_method'] }}</td>
                                <td>{{ $row['payment_status'] }}</td>
                                <td class="end">{{ $money($row['total']) }}</td>
                                <td class="end">{{ $money($row['paid']) }}</td>
                                <td class="end {{ $row['remaining'] > 0 ? 'tx-bad' : '' }}">{{ $money($row['remaining']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="empty">لا توجد بيانات في هذه الفترة</td></tr>
                        @endforelse
                    </tbody>
                    @if (count($statement['rows']))
                        <tfoot>
                            <tr>
                                <td colspan="5">الإجمالي</td>
                                <td class="end">{{ $money($s['total_purchases']) }}</td>
                                <td class="end">{{ $money($s['total_paid']) }}</td>
                                <td class="end">{{ $money($s['total_remaining']) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif
@endsection
