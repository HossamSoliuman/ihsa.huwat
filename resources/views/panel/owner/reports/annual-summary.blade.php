@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @php $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v); @endphp

    @include('panel.owner.reports.partials.head')

    <form method="GET" action="{{ route('panel.owner.reports.show', $key) }}" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field" style="flex:1;min-width:14rem"><span>القارب</span>
            <select class="select" name="boat_id">
                <option value="">كل القوارب</option>
                @foreach ($boats as $b)<option value="{{ $b->id }}" @selected($boatId === $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">@include('partials.icon', ['name' => 'search']) عرض</button>
    </form>

    <div class="card">
        @include('partials.section-head', ['icon' => 'lock', 'title' => 'السنوات المقفلة'])
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>السنة</th>
                        <th>الأشهر المقفلة</th>
                        <th class="end">المبيعات</th>
                        <th class="end">إجمالي المصروفات</th>
                        <th class="end">صافي الربح</th>
                        <th class="end">حصة البحارة</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($years as $row)
                        @php $t = $row['summary']['totals']; @endphp
                        <tr>
                            <td class="num" style="font-weight:700">{{ $row['year'] }}</td>
                            <td class="num"><bdi dir="ltr">{{ $row['summary']['closed_count'] }} / 12</bdi></td>
                            <td class="end">{{ $money($t['gross_sales']) }}</td>
                            <td class="end tx-bad">{{ $money($t['total_expenses']) }}</td>
                            <td class="end {{ $t['net_profit'] >= 0 ? 'tx-good' : 'tx-bad' }}" style="font-weight:700">{{ $money($t['net_profit']) }}</td>
                            <td class="end">{{ $money($t['crew_share']) }}</td>
                            <td>
                                @if ($t['net_profit'] > 0)
                                    <span class="badge badge-ok">رابحة</span>
                                @else
                                    <span class="badge badge-danger">خاسرة</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('panel.owner.reports.show', ['report' => $key, 'mode' => 'print', 'year' => $row['year']] + ($boatId ? ['boat_id' => $boatId] : [])) }}" target="_blank" class="btn btn-outline" style="padding:.3rem .6rem;font-size:.74rem">@include('partials.icon', ['name' => 'printer']) طباعة</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty">لا توجد أشهر مقفلة بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
