@extends('layouts.sheet')

@section('title', 'hawat_expenses_report')
@section('orientation', 'landscape')
@section('width', '1100px')

@section('content')
    <header class="head">
        <div>
            <h1>{{ $owner->name }}</h1>
            <p>هاتف: <span class="num" dir="ltr">{{ $owner->phone ?? '—' }}</span></p>
        </div>
    </header>

    <div class="title">كشف المصروفات</div>

    @if ($filters)
        <div class="chips">
            @foreach ($filters as $label => $value)<span>{{ $label }}: <b>{{ $value }}</b></span>@endforeach
        </div>
    @endif

    <table>
        <thead>
            <tr><th>السند</th><th>التاريخ</th><th>الفئة</th><th>القارب / الرحلة</th><th>المورد</th><th>المبلغ</th><th>الخصم</th><th>الضريبة</th><th>الإجمالي</th><th>المدفوع</th><th>الحالة</th></tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td><span class="num">{{ $row->expense_number }}</span>@if ($row->description)<div class="muted">{{ $row->description }}</div>@endif</td>
                    <td class="num">{{ $row->date->format('Y-m-d') }}</td>
                    <td>{{ $row->category?->name }}<div class="muted">{{ $row->category?->group?->name }}</div></td>
                    <td>{{ $row->boat?->name ?? 'عام' }}@if ($row->trip)<div class="muted num">{{ $row->trip->trip_number }}</div>@endif</td>
                    <td>{{ $row->vendor?->name ?? '—' }}</td>
                    <td class="num">{{ number_format($row->subtotal, 2) }}</td>
                    <td class="num">{{ number_format($row->discount, 2) }}</td>
                    <td class="num">{{ number_format($row->vat_amount, 2) }}</td>
                    <td class="num" style="font-weight:700">{{ number_format($row->total, 2) }}</td>
                    <td class="num">{{ number_format($row->paid_amount, 2) }}</td>
                    <td>{{ $row->paymentStatus?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="11" style="text-align:center;padding:18px">لا مصروفات ضمن التصفية</td></tr>
            @endforelse
        </tbody>
        @if ($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="5">المجموع — <span class="num">{{ $rows->count() }}</span> سند</td>
                    <td class="num">{{ number_format($rows->sum('subtotal'), 2) }}</td>
                    <td class="num">{{ number_format($rows->sum('discount'), 2) }}</td>
                    <td class="num">{{ number_format($rows->sum('vat_amount'), 2) }}</td>
                    <td class="num">{{ number_format($rows->sum('total'), 2) }}</td>
                    <td class="num">{{ number_format($rows->sum('paid_amount'), 2) }}</td>
                    <td class="num">{{ number_format($rows->sum('total') - $rows->sum('paid_amount'), 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    @if ($rows->isNotEmpty())
        @php $byGroup = $rows->groupBy(fn ($r) => $r->category?->group?->name ?? '—')->map->sum('total')->sortDesc(); @endphp
        <div class="totals" style="width:360px">
            @foreach ($byGroup as $name => $sum)
                <div><span>{{ $name }}</span><span class="num">{{ number_format($sum, 2) }}</span></div>
            @endforeach
            <div class="grand"><span>إجمالي المصروفات</span><span class="num">{{ number_format($rows->sum('total'), 2) }} ر.س</span></div>
        </div>
    @endif
@endsection
