@extends('layouts.sheet')

@section('title', 'hawat_depreciation_'.$year)
@section('orientation', 'landscape')
@section('width', '1100px')

@section('content')
    @php $monthNames = \App\Models\FishingSeason::MONTHS; @endphp
    <header class="head">
        <div>
            <h1>{{ $owner->name }}</h1>
            <p>هاتف: <span class="num" dir="ltr">{{ $owner->phone ?? '—' }}</span></p>
        </div>
    </header>

    <div class="title">جدول إهلاك الأصول — <span class="num">{{ $year }}</span></div>

    <div class="chips">
        <span>الطريقة: <b>القسط الثابت شهريًا</b></span>
        <span>القارب: <b>{{ $boat?->name ?? 'كل القوارب' }}</b></span>
        <span>إهلاك السنة: <b class="num">{{ number_format($schedule['year_total'], 2) }}</b> ر.س</span>
    </div>

    <table>
        <thead><tr><th></th>@foreach ($monthNames as $m)<th>{{ $m }}</th>@endforeach</tr></thead>
        <tbody>
            <tr><td>الشهر</td>@foreach ($schedule['months'] as $row)<td class="num">{{ number_format($row['total'], 2) }}</td>@endforeach</tr>
            <tr><td>المتراكم</td>@foreach ($schedule['months'] as $row)<td class="num">{{ number_format($row['accumulated'], 2) }}</td>@endforeach</tr>
        </tbody>
    </table>

    <table>
        <thead><tr><th>الأصل</th><th>النوع</th><th>الشراء</th><th>التكلفة</th><th>العمر</th><th>القسط</th><th>أشهر السنة</th><th>إهلاك السنة</th><th>المتراكم</th><th>القيمة الدفترية</th></tr></thead>
        <tbody>
            @forelse ($schedule['assets'] as $r)
                <tr>
                    <td>{{ $r['asset']->name }}@if ($r['asset']->boat)<div class="muted">{{ $r['asset']->boat->name }}</div>@endif</td>
                    <td>{{ $r['asset']->type?->name }}</td>
                    <td class="num">{{ $r['asset']->purchase_date->format('Y-m-d') }}</td>
                    <td class="num">{{ number_format($r['asset']->purchase_cost, 2) }}</td>
                    <td class="num">{{ $r['asset']->useful_life_years }}</td>
                    <td class="num">{{ number_format($r['monthly'], 2) }}</td>
                    <td class="num">{{ $r['months_charged'] }}</td>
                    <td class="num">{{ number_format($r['year_total'], 2) }}</td>
                    <td class="num">{{ number_format($r['accumulated'], 2) }}</td>
                    <td class="num">{{ number_format($r['book_value'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align:center;padding:18px">لا إهلاك في هذه السنة</td></tr>
            @endforelse
        </tbody>
        @if ($schedule['assets'])
            <tfoot><tr><td colspan="7">المجموع</td><td class="num">{{ number_format($schedule['year_total'], 2) }}</td><td colspan="2"></td></tr></tfoot>
        @endif
    </table>
@endsection
