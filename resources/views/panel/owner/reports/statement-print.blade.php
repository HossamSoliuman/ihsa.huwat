@extends('layouts.sheet')

@section('title', 'hawat_'.$key.'_'.$file_suffix)
@section('orientation', 'portrait')

@section('content')
    @php
        $t = $statement['table'];
        $isCustomer = $key === 'customer-statement';
    @endphp
    @include('panel.owner.reports.partials.sheet-head')

    <div class="boxes">
        <div>
            <h3>{{ $isCustomer ? 'العميل' : 'المورد' }}</h3>
            <dl>
                <dt>الاسم</dt><dd>{{ $party->name }}</dd>
                <dt>الجوال</dt><dd><span class="num" dir="ltr">{{ $party->phone ?? '—' }}</span></dd>
                <dt>آخر {{ $isCustomer ? 'فاتورة' : 'سند' }}</dt><dd><span class="num">{{ $statement['last']?->format('Y-m-d') ?? '—' }}</span></dd>
            </dl>
        </div>
        <div>
            <h3>ملخص الفترة</h3>
            <dl>
                <dt>{{ $isCustomer ? 'الفواتير' : 'السندات' }}</dt><dd class="num">{{ $statement['count'] }}</dd>
                <dt>القيمة</dt><dd class="num">{{ number_format($t['totals']['debit'] ?? 0, 2) }} ر.س</dd>
                <dt>{{ $isCustomer ? 'المحصّل' : 'المسدَّد' }}</dt><dd class="num">{{ number_format($t['totals']['credit'] ?? 0, 2) }} ر.س</dd>
                <dt>الرصيد في آخر الفترة</dt><dd class="num" style="font-weight:800">{{ number_format($statement['closing'], 2) }} ر.س</dd>
            </dl>
        </div>
    </div>

    <table>
        <thead>
            <tr>@foreach ($t['columns'] as $column)<th>{{ $column['label'] }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @if ($statement['opening'] !== null)
                <tr>
                    <td><bdi class="num" dir="ltr">{{ $from }}</bdi></td>
                    <td colspan="{{ count($t['columns']) - 2 }}" style="font-weight:700">رصيد افتتاحي</td>
                    <td class="num strong">{{ number_format($statement['opening'], 2) }}</td>
                </tr>
            @endif
            @forelse ($t['rows'] as $row)
                <tr>
                    @foreach ($t['columns'] as $column)
                        @include('panel.owner.reports.partials.cell', ['first' => $loop->first])
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($t['columns']) }}" style="text-align:center;padding:18px;color:#64748b">لا حركات في هذه الفترة</td></tr>
            @endforelse
        </tbody>
        @if ($t['totals'])
            <tfoot>
                <tr>
                    @foreach ($t['columns'] as $column)
                        @if ($loop->first)
                            <td>مجموع الفترة</td>
                        @elseif (array_key_exists($column['key'], $t['totals']))
                            @include('panel.owner.reports.partials.cell', ['row' => $t['totals'], 'first' => false])
                        @else
                            <td></td>
                        @endif
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="totals">
        @if ($statement['opening'] !== null)
            <div><span>الرصيد الافتتاحي</span><span class="num">{{ number_format($statement['opening'], 2) }}</span></div>
        @endif
        <div><span>+ القيمة</span><span class="num">{{ number_format($t['totals']['debit'] ?? 0, 2) }}</span></div>
        <div><span>− {{ $isCustomer ? 'المحصّل' : 'المسدَّد' }}</span><span class="num">{{ number_format($t['totals']['credit'] ?? 0, 2) }}</span></div>
        <div class="grand"><span>{{ $isCustomer ? 'المستحق لك' : 'المستحق عليك' }}</span><span class="num">{{ number_format($statement['closing'], 2) }} ر.س</span></div>
    </div>

    <ul class="notes">
        @foreach ($statement['notes'] as $note)<li>{{ $note }}</li>@endforeach
        <li>الرصيد على كل الفترات: <span class="num">{{ number_format($statement['all_time'], 2) }}</span> ر.س.</li>
    </ul>

    <div class="signs">
        <div><span></span>المالك</div>
        <div><span></span>{{ $isCustomer ? 'العميل' : 'المورد' }}</div>
        <div><span></span>الختم</div>
    </div>
@endsection
