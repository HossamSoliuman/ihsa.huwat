{{-- نشاط عدّادي الشركة (CompanyDashboard::activity) — في الرئيسة بلا أزرار، وفي صفحة العدّادين بأزرار الإيقاف والنقل. --}}
<div class="table-card" style="{{ $actions ? '' : 'border:0' }}">
    <table class="data-table">
        <thead>
            <tr><th>العدّاد</th><th>الجوال</th><th>الميناء</th><th>رحلات عدّها</th><th>المعدود</th><th>آخر عدّ</th><th>الحالة</th>@if ($actions)<th></th>@endif</tr>
        </thead>
        <tbody>
            @forelse ($counters as $counter)
                <tr>
                    <td style="font-weight:600">{{ $counter->name }}<div class="card-sub num">{{ $counter->employee_number }}</div></td>
                    <td class="num" dir="ltr" style="text-align:right">{{ $counter->phone ?? '—' }}</td>
                    <td>{{ $counter->port?->name }}</td>
                    <td class="num">{{ number_format($counter->activity_trips) }}</td>
                    <td class="num">{{ number_format($counter->activity_kg, 1) }} <span class="card-sub">كجم</span></td>
                    <td class="num" style="font-size:.74rem">{{ $counter->activity_last ? \Illuminate\Support\Carbon::parse($counter->activity_last)->format('Y-m-d') : '—' }}</td>
                    <td>
                        <span class="badge {{ $counter->isSuspended() ? 'badge-danger' : 'badge-ok' }}">{{ $counter->status }}</span>
                        @if ($counter->isSuspended() && $counter->suspension_reason)
                            <div class="card-sub">{{ $counter->suspension_reason }}</div>
                        @endif
                    </td>
                    @if ($actions)
                        <td>
                            @include('panel.counters.partials.actions', [
                                'routes' => ['suspend' => 'panel.company.counters.suspend', 'reactivate' => 'panel.company.counters.reactivate', 'transfer' => 'panel.company.counters.transfer'],
                                'ports' => $ports,
                                'canReactivate' => ! $counter->isSuspendedByMinistry(),
                            ])
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $actions ? 8 : 7 }}" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لم توظّف الشركة عدّادًا بعد — اعتمد طلبًا من طلبات التوظيف.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
