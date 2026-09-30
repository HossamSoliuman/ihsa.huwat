@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @php $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v); @endphp

    @include('panel.owner.reports.partials.head')
    @include('panel.owner.reports.partials.statement-filter', [
        'entityParam' => 'person_id',
        'entityLabel' => 'الطاقم',
        'placeholder' => 'اختر الفرد',
        'selectedId' => $person?->id,
        'groups' => [['label' => 'الكباتن', 'items' => $captains], ['label' => 'الصيادون', 'items' => $fishers]],
    ])

    @if (! $person)
        <div class="report-prompt">اختر من القائمة أعلاه لعرض كشف الحساب.</div>
    @else
        @php $t = $statement['totals']; @endphp
        <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
            @include('partials.stat-card', ['label' => 'إجمالي المستحقات', 'value' => number_format($t['due'], 2), 'unit' => 'ر.س', 'icon' => 'calculator', 'tone' => 'muted'])
            @include('partials.stat-card', ['label' => 'إجمالي المدفوع', 'value' => number_format($t['paid'], 2), 'unit' => 'ر.س', 'icon' => 'check-circle', 'tone' => 'success'])
            @include('partials.stat-card', ['label' => 'إجمالي غير المدفوع', 'value' => number_format($t['unpaid'], 2), 'unit' => 'ر.س', 'icon' => 'alert-triangle', 'tone' => $t['unpaid'] > 0 ? 'danger' : 'success'])
            @include('partials.stat-card', ['label' => 'عدد الأشهر', 'value' => (string) $t['months'], 'icon' => 'calendar', 'tone' => 'primary'])
        </div>

        <div class="card">
            <div class="card-title" style="margin-bottom:.75rem;display:flex;justify-content:space-between;gap:1rem">
                <span>{{ $person->name }}</span>
                <span style="font-weight:500;color:hsl(var(--muted-foreground))">{{ $person->boat?->name ?? '—' }}</span>
            </div>
            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الفترة (شهر / سنة)</th>
                            <th class="end">المستحق</th>
                            <th class="end">المدفوع</th>
                            <th class="end">غير المدفوع</th>
                            <th>تاريخ الدفع</th>
                            <th>الدفع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($statement['rows'] as $row)
                            <tr>
                                <td class="num">{{ $loop->iteration }}</td>
                                <td><a href="{{ route('panel.owner.payrolls.show', $row['payroll_id']) }}"><bdi class="num" dir="ltr">{{ $row['period'] }}</bdi></a></td>
                                <td class="end">{{ $money($row['due']) }}</td>
                                <td class="end">{{ $money($row['paid']) }}</td>
                                <td class="end {{ $row['unpaid'] > 0 ? 'tx-bad' : '' }}">{{ $money($row['unpaid']) }}</td>
                                <td><bdi class="num" dir="ltr">{{ $row['paid_date'] ?? '—' }}</bdi></td>
                                <td>
                                    @if ($row['is_paid'])
                                        <span class="badge badge-ok">مدفوع</span>
                                    @else
                                        <span class="badge badge-danger">غير مدفوع</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty">لا توجد بيانات في هذه الفترة</td></tr>
                        @endforelse
                    </tbody>
                    @if (count($statement['rows']))
                        <tfoot>
                            <tr>
                                <td colspan="2">الإجمالي</td>
                                <td class="end">{{ $money($t['due']) }}</td>
                                <td class="end">{{ $money($t['paid']) }}</td>
                                <td class="end">{{ $money($t['unpaid']) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif
@endsection
