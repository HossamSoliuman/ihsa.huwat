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
                    <th>النوع</th>
                    <th class="end">الوزن المصطاد</th>
                    <th class="end">قيمة المصطاد</th>
                    <th class="end">الوزن المباع</th>
                    <th class="end">قيمة المباع</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td><a href="{{ route('panel.owner.reports.show', ['report' => 'fish-quantity', 'fish_id' => $row['fish_id'], 'from' => $from, 'to' => $to]) }}" title="كميات النوع">{{ $row['fish_name'] }}</a></td>
                        <td class="end num">{{ number_format($row['caught_weight'], 2) }} {{ $row['unit_name'] }}</td>
                        <td class="end">{{ $money($row['caught_value']) }}</td>
                        <td class="end num">{{ number_format($row['sold_weight'], 2) }} {{ $row['unit_name'] }}</td>
                        <td class="end" style="font-weight:700">{{ $money($row['sold_value']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">لا توجد بيانات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
