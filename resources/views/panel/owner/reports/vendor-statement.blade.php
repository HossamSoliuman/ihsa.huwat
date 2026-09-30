@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @php $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v); @endphp

    @include('panel.owner.reports.partials.head')
    @include('panel.owner.reports.partials.statement-filter', [
        'entityParam' => 'vendor_id',
        'entityLabel' => 'المورد',
        'placeholder' => 'اختر المورد',
        'selectedId' => $vendor?->id,
        'groups' => [['label' => null, 'items' => $vendors]],
    ])

    @if (! $vendor)
        <div class="report-prompt">اختر من القائمة أعلاه لعرض كشف الحساب.</div>
    @else
        <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
            @include('partials.stat-card', ['label' => 'إجمالي الإنفاق', 'value' => number_format($statement['total_expenses'], 2), 'unit' => 'ر.س', 'icon' => 'receipt', 'tone' => 'muted'])
            @include('partials.stat-card', ['label' => 'الرصيد المستحق', 'value' => number_format($statement['total_due'], 2), 'unit' => 'ر.س', 'icon' => 'alert-triangle', 'tone' => $statement['total_due'] > 0 ? 'danger' : 'success'])
        </div>

        <div class="card">
            <div class="card-title" style="margin-bottom:.75rem">{{ $vendor->name }}</div>
            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم السند</th>
                            <th>الفئة</th>
                            <th>الرحلة</th>
                            <th>التاريخ</th>
                            <th>الحالة</th>
                            <th class="end">المبلغ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($statement['rows'] as $row)
                            <tr>
                                <td class="num">{{ $loop->iteration }}</td>
                                <td><a href="{{ route('panel.owner.expenses', ['search' => $row['number']]) }}"><bdi class="num" dir="ltr">{{ $row['number'] }}</bdi></a></td>
                                <td>{{ $row['category'] }}</td>
                                <td><bdi class="num" dir="ltr">{{ $row['trip'] }}</bdi></td>
                                <td><bdi class="num" dir="ltr">{{ $row['date'] }}</bdi></td>
                                <td><span class="badge {{ $row['is_paid'] ? 'badge-ok' : 'badge-warn' }}">{{ $row['status'] }}</span></td>
                                <td class="end">{{ $money($row['amount']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty">لا توجد بيانات في هذه الفترة</td></tr>
                        @endforelse
                    </tbody>
                    @if (count($statement['rows']))
                        <tfoot>
                            <tr>
                                <td colspan="6">الإجمالي</td>
                                <td class="end">{{ $money($statement['total_expenses']) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif
@endsection
