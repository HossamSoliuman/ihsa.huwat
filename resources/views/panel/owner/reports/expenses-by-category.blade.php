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
                    <th>الفئة</th>
                    <th>النوع</th>
                    <th class="end">عدد العمليات</th>
                    <th class="end">المبلغ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>
                            @if ($row['category_id'])
                                <a href="{{ route('panel.owner.expenses', array_filter(['category' => $row['category_id'], 'boat' => $boatId, 'from' => $from, 'to' => $to])) }}" title="سندات الفئة">{{ $row['category'] }}</a>
                            @else
                                {{ $row['category'] }}
                            @endif
                        </td>
                        <td>{{ $row['type'] ?? '—' }}</td>
                        <td class="end num">{{ number_format($row['count']) }}</td>
                        <td class="end" style="font-weight:700">{{ $money($row['amount']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">لا توجد بيانات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
            @if (count($rows))
                <tfoot>
                    <tr>
                        <td colspan="3">الإجمالي</td>
                        <td class="end">{{ $money($total) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
@endsection
