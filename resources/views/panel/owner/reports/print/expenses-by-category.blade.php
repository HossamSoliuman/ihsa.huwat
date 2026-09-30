@extends('layouts.report')

@section('title', $meta['title'].' '.$from.' — '.$to)

@section('content')
    @php
        $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v);
        $count = array_sum(array_column($rows, 'count'));
    @endphp

    @include('panel.owner.reports.print.partials.masthead', ['subtitle' => 'Trip Expenses by Category'])
    @include('panel.owner.reports.print.partials.info')
    @include('panel.owner.reports.print.partials.stats', ['items' => [
        ['label' => 'عدد الفئات', 'value' => count($rows)],
        ['label' => 'إجمالي العمليات', 'value' => number_format($count)],
        ['label' => 'إجمالي المصروفات', 'value' => $total, 'money' => true],
    ]])

    <table class="report-table">
        <thead>
            <tr><th>الفئة</th><th>النوع</th><th>عدد العمليات</th><th>المبلغ</th></tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['category'] }}</td>
                    <td>{{ $row['type'] ?? '—' }}</td>
                    <td class="col-num">{{ number_format($row['count']) }}</td>
                    <td class="col-num" style="font-weight:700">{{ $money($row['amount']) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted" style="padding:30px">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('panel.owner.reports.print.partials.summary', ['rows' => [
        ['label' => 'عدد الفئات', 'value' => count($rows)],
        ['label' => 'إجمالي العمليات', 'value' => number_format($count)],
        ['label' => 'الإجمالي', 'value' => $total, 'money' => true, 'highlight' => true],
    ]])
@endsection
