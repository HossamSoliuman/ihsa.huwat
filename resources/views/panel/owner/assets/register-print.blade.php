@extends('layouts.sheet')

@section('title', 'hawat_assets_register')
@section('orientation', 'landscape')
@section('width', '1100px')

@section('content')
    <header class="head">
        <div>
            <h1>{{ $owner->name }}</h1>
            <p>هاتف: <span class="num" dir="ltr">{{ $owner->phone ?? '—' }}</span></p>
        </div>
        <div style="text-align:left"><p>الموقف في: <span class="num">{{ now()->format('Y-m-d') }}</span></p></div>
    </header>

    <div class="title">سجل الأصول</div>

    <table>
        <thead><tr><th>الأصل</th><th>النوع</th><th>القارب</th><th>الشراء</th><th>التكلفة</th><th>الخردة</th><th>العمر</th><th>القسط الشهري</th><th>الأشهر</th><th>المتراكم</th><th>القيمة الدفترية</th><th>الحالة</th></tr></thead>
        <tbody>
            @forelse ($register['rows'] as $r)
                @php $a = $r['asset']; @endphp
                <tr>
                    <td>{{ $a->name }}</td>
                    <td>{{ $a->type?->name }}</td>
                    <td>{{ $a->boat?->name ?? '—' }}</td>
                    <td class="num">{{ $a->purchase_date->format('Y-m-d') }}</td>
                    <td class="num">{{ number_format($a->purchase_cost, 2) }}</td>
                    <td class="num">{{ number_format($a->salvage_value, 2) }}</td>
                    <td class="num">{{ $a->useful_life_years }}</td>
                    <td class="num">{{ number_format($r['monthly'], 2) }}</td>
                    <td class="num">{{ $r['months_charged'] }}/{{ $r['total_months'] }}</td>
                    <td class="num">{{ number_format($r['accumulated'], 2) }}</td>
                    <td class="num">{{ number_format($r['book_value'], 2) }}</td>
                    <td>{{ $a->status }}@if ($a->disposed_at)<div class="muted num">{{ $a->disposed_at->format('Y-m-d') }}</div>@endif</td>
                </tr>
            @empty
                <tr><td colspan="12" style="text-align:center;padding:18px">لا أصول</td></tr>
            @endforelse
        </tbody>
        @if ($register['rows']->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="4">المجموع — <span class="num">{{ $register['totals']['count'] }}</span> أصل</td>
                    <td class="num">{{ number_format($register['totals']['cost'], 2) }}</td>
                    <td colspan="4"></td>
                    <td class="num">{{ number_format($register['totals']['accumulated'], 2) }}</td>
                    <td class="num">{{ number_format($register['totals']['book_value'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection
