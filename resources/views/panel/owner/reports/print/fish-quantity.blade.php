@extends('layouts.report')

@section('title', $meta['title'].' '.$from.' — '.$to)

@section('content')
    @php $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v); @endphp

    @include('panel.owner.reports.print.partials.masthead')

    <table class="info-bar">
        <tr>
            <td><span class="ib-label">من تاريخ</span><span class="ib-value"><bdi dir="ltr">{{ $from }}</bdi></span></td>
            <td><span class="ib-label">إلى تاريخ</span><span class="ib-value"><bdi dir="ltr">{{ $to }}</bdi></span></td>
            <td><span class="ib-label">القارب</span><span class="ib-value">{{ $boat?->name ?? 'الكل' }}</span></td>
            <td><span class="ib-label">اسم الرحلة</span><span class="ib-value">{{ $trip?->trip_number ?? 'الكل' }}</span></td>
            <td><span class="ib-label">نوع السمك</span><span class="ib-value">{{ $fish?->name_ar ?? 'الكل' }}</span></td>
        </tr>
    </table>

    @include('panel.owner.reports.print.partials.stats', ['items' => [
        ['label' => 'عدد الأسماك', 'value' => $stocks->pluck('fish_id')->unique()->count()],
        ['label' => 'إجمالي الوزن', 'value' => number_format($stocks->sum('weight'), 2).' كجم'],
        ['label' => 'إجمالي السعر', 'value' => $stocks->sum('total'), 'money' => true],
    ]])

    @if ($stocks->isEmpty())
        <p class="note">لا توجد بيانات للتصفية المختارة — حاول ضبط الفلاتر أو تحقق من وجود سجلات.</p>
    @else
        <table class="report-table block">
            <thead>
                <tr>
                    <th style="width:6%">#</th>
                    <th class="col-text" style="width:30%">نوع السمك</th>
                    <th class="col-num" style="width:16%">إجمالي الوزن</th>
                    <th style="width:16%">الوحدة</th>
                    <th class="col-num" style="width:16%">سعر الكيلو</th>
                    <th class="col-num" style="width:16%">إجمالي السعر</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($stocks as $stock)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="col-text">{{ $stock['fish_name'] }}</td>
                        <td class="col-num">{{ number_format($stock['weight'], 2) }}</td>
                        <td>{{ $stock['unit'] }}</td>
                        <td class="col-num">{{ $money($stock['price_per_kg']) }}</td>
                        <td class="col-num">{{ $money($stock['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">المجموع (كجم)</td>
                    <td class="col-num">{{ number_format($stocks->sum('weight'), 2) }}</td>
                    <td colspan="3">كجم</td>
                </tr>
                <tr class="net-row">
                    <td colspan="5">إجمالي السعر</td>
                    <td class="col-num">{{ $money($stocks->sum('total')) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    @include('panel.owner.reports.print.partials.footer')
@endsection
