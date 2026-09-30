@extends('layouts.report')

@section('title', $meta['title'].' '.$from.' — '.$to)

@section('content')
    @php
        $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v);
        $caughtWeight = array_sum(array_column($rows, 'caught_weight'));
        $caughtValue = array_sum(array_column($rows, 'caught_value'));
        $soldWeight = array_sum(array_column($rows, 'sold_weight'));
        $soldValue = array_sum(array_column($rows, 'sold_value'));
    @endphp

    @include('panel.owner.reports.print.partials.masthead', ['subtitle' => 'Production by Species Report'])
    @include('panel.owner.reports.print.partials.info')
    @include('panel.owner.reports.print.partials.stats', ['items' => [
        ['label' => 'عدد الأنواع', 'value' => count($rows)],
        ['label' => 'الوزن المصطاد', 'value' => number_format($caughtWeight, 2)],
        ['label' => 'الوزن المباع', 'value' => number_format($soldWeight, 2)],
        ['label' => 'قيمة المباع', 'value' => $soldValue, 'money' => true],
    ]])

    <table class="report-table">
        <thead>
            <tr><th>النوع</th><th>الوزن المصطاد</th><th>قيمة المصطاد</th><th>الوزن المباع</th><th>قيمة المباع</th></tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['fish_name'] }}</td>
                    <td class="col-num">{{ number_format($row['caught_weight'], 2) }} {{ $row['unit_name'] }}</td>
                    <td class="col-num">{{ $money($row['caught_value']) }}</td>
                    <td class="col-num">{{ number_format($row['sold_weight'], 2) }} {{ $row['unit_name'] }}</td>
                    <td class="col-num" style="font-weight:700">{{ $money($row['sold_value']) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted" style="padding:30px">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('panel.owner.reports.print.partials.summary', ['rows' => [
        ['label' => 'الوزن المصطاد', 'value' => number_format($caughtWeight, 2)],
        ['label' => 'قيمة المصطاد', 'value' => $caughtValue, 'money' => true],
        ['label' => 'الوزن المباع', 'value' => number_format($soldWeight, 2)],
        ['label' => 'قيمة المباع', 'value' => $soldValue, 'money' => true, 'highlight' => true],
    ]])
@endsection
