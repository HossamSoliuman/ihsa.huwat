@extends('layouts.report')

@section('title', 'كشف رواتب - '.$person->name)

@section('content')
    @php
        $money = fn ($v) => \App\Services\Owner\OwnerReports::money($v);
        $t = $statement['totals'];
    @endphp

    @include('panel.owner.reports.print.partials.masthead', ['title' => 'كشف رواتب - '.$person->name, 'subtitle' => 'بيان المستحقات المدفوعة وغير المدفوعة'])

    <table class="info-bar">
        <tr>
            <td><span class="ib-label">رقم القيد</span><span class="ib-value"><bdi dir="ltr">#{{ str_pad((string) $person->id, 8, '0', STR_PAD_LEFT) }}</bdi></span></td>
            <td><span class="ib-label">الاسم</span><span class="ib-value">{{ $person->name }}</span></td>
            <td><span class="ib-label">القارب</span><span class="ib-value">{{ $person->boat?->name ?? '—' }}</span></td>
        </tr>
    </table>

    @include('panel.owner.reports.print.partials.stats', ['items' => [
        ['label' => 'إجمالي المستحقات', 'value' => $t['due'], 'money' => true],
        ['label' => 'إجمالي المدفوع', 'value' => $t['paid'], 'money' => true, 'tone' => 'good'],
        ['label' => 'إجمالي غير المدفوع', 'value' => $t['unpaid'], 'money' => true, 'tone' => 'bad'],
        ['label' => 'عدد الأشهر', 'value' => (string) $t['months']],
    ]])

    <div class="section-bar">تفاصيل المستحقات الشهرية</div>
    <table class="report-table block">
        <thead>
            <tr>
                <th style="width:32px">#</th>
                <th>الفترة (شهر / سنة)</th>
                <th class="col-num">المستحق</th>
                <th class="col-num">المدفوع</th>
                <th class="col-num">غير المدفوع</th>
                <th>تاريخ الدفع</th>
                <th class="col-text">ملاحظات</th>
                <th>الدفع</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($statement['rows'] as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><bdi dir="ltr">{{ $row['period'] }}</bdi></td>
                    <td class="col-num">{{ $money($row['due']) }}</td>
                    <td class="col-num">{{ $money($row['paid']) }}</td>
                    <td class="col-num">{{ $money($row['unpaid']) }}</td>
                    <td><bdi dir="ltr">{{ $row['paid_date'] ?? '—' }}</bdi></td>
                    <td class="col-text">{{ $row['notes'] ?: '—' }}</td>
                    <td>
                        @if ($row['is_paid'])
                            <span class="badge bg-success">مدفوع</span>
                        @else
                            <span class="tx-bad" style="font-weight:600;white-space:nowrap">غير مدفوع</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">لا توجد مستحقات</td></tr>
            @endforelse
        </tbody>
        @if (count($statement['rows']))
            <tfoot>
                <tr class="net-row">
                    <th colspan="2" class="col-text">الإجمالي</th>
                    <th class="col-num">{{ $money($t['due']) }}</th>
                    <th class="col-num">{{ $money($t['paid']) }}</th>
                    <th class="col-num">{{ $money($t['unpaid']) }}</th>
                    <th colspan="3"></th>
                </tr>
            </tfoot>
        @endif
    </table>

    @if (count($statement['rows']))
        @include('panel.owner.reports.print.partials.amount-words', ['label' => 'المتبقي كتابةً', 'amount' => $t['unpaid']])
    @endif

    @include('panel.owner.reports.print.partials.signatures', ['items' => ['المستلم', 'المحاسب', 'المدير العام']])
    @include('panel.owner.reports.print.partials.footer')
@endsection
