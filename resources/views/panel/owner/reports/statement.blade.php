@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @php $isCustomer = $key === 'customer-statement'; @endphp

    @include('panel.owner.reports.partials.web-head', ['ready' => $party !== null, 'period' => $party ? $party->name : null])

    <form method="GET" action="{{ route('panel.owner.reports.show', $key) }}" class="filter-bar" style="margin-bottom:1.25rem;display:flex;flex-wrap:wrap;align-items:flex-end;gap:.65rem">
        <label class="field" style="min-width:14rem"><span>{{ $isCustomer ? 'العميل' : 'المورد' }}</span>
            <select class="select" name="{{ $param }}" required>
                <option value="">اختر</option>
                @foreach ($parties as $p)<option value="{{ $p->id }}" @selected($party?->id === $p->id)>{{ $p->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>من</span><input class="input" type="date" name="from" value="{{ $from }}" dir="ltr"></label>
        <label class="field"><span>إلى</span><input class="input" type="date" name="to" value="{{ $to }}" dir="ltr"></label>
        <button class="btn btn-primary">@include('partials.icon', ['name' => 'search']) عرض الكشف</button>
    </form>

    @if ($party === null)
        <div class="card" style="padding:2.5rem;text-align:center;color:hsl(var(--muted-foreground))">
            {{ $parties->isEmpty() ? ($isCustomer ? 'لا عملاء بعد — أضفهم من صفحة العملاء.' : 'لا موردين بعد — أضفهم من صفحة الموردين.') : 'اختر '.($isCustomer ? 'العميل' : 'المورد').' لعرض كشف حسابه.' }}
        </div>
    @else
        @php $t = $statement['table']; @endphp
        @include('panel.owner.reports.partials.web-kpis', ['kpis' => array_values(array_filter([
            $statement['opening'] !== null ? ['label' => 'الرصيد الافتتاحي', 'value' => $statement['opening'], 'format' => 'money', 'icon' => 'history'] : null,
            ['label' => $isCustomer ? 'الفواتير' : 'السندات', 'value' => $statement['count'], 'format' => 'int', 'icon' => 'file-text'],
            ['label' => 'القيمة', 'value' => $t['totals']['debit'] ?? 0, 'format' => 'money', 'icon' => 'coins'],
            ['label' => $isCustomer ? 'المحصّل' : 'المسدَّد', 'value' => $t['totals']['credit'] ?? 0, 'format' => 'money', 'icon' => 'check-circle'],
            ['label' => $isCustomer ? 'المستحق لك' : 'المستحق عليك', 'value' => $statement['closing'], 'format' => 'money', 'strong' => true, 'icon' => 'calculator'],
        ]))])

        <div class="card" style="margin-bottom:1.25rem">
            @include('partials.section-head', ['icon' => 'file-text', 'title' => 'الحركات', 'note' => $period])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr>@foreach ($t['columns'] as $column)<th>{{ $column['label'] }}</th>@endforeach</tr></thead>
                    <tbody>
                        @if ($statement['opening'] !== null)
                            <tr style="background:hsl(var(--muted) / .35)">
                                <td><bdi class="num" dir="ltr">{{ $from }}</bdi></td>
                                <td colspan="{{ count($t['columns']) - 2 }}" style="font-weight:700">رصيد افتتاحي</td>
                                <td class="num" style="font-weight:700">{{ number_format($statement['opening'], 2) }}</td>
                            </tr>
                        @endif
                        @forelse ($t['rows'] as $row)
                            <tr>
                                @foreach ($t['columns'] as $column)
                                    @include('panel.owner.reports.partials.cell', ['web' => true, 'first' => $loop->first])
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($t['columns']) }}" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا حركات في هذه الفترة</td></tr>
                        @endforelse
                    </tbody>
                    @if ($t['totals'])
                        <tfoot>
                            <tr style="font-weight:700;background:hsl(var(--muted) / .5)">
                                @foreach ($t['columns'] as $column)
                                    @if ($loop->first)
                                        <td>مجموع الفترة</td>
                                    @elseif (array_key_exists($column['key'], $t['totals']))
                                        @include('panel.owner.reports.partials.cell', ['row' => $t['totals'], 'web' => true, 'first' => false])
                                    @else
                                        <td></td>
                                    @endif
                                @endforeach
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        @include('panel.owner.reports.partials.web-notes', ['notes' => array_merge($statement['notes'], ['الرصيد على كل الفترات: '.number_format($statement['all_time'], 2).' ر.س.'])])
    @endif
@endsection
