<x-information.section-head title="إجراءات مطلوبة" subtitle="قوائم قصيرة مرتبة لتبدأ منها أعمال اليوم" icon="bell" />

<section class="info-dashboard-grid info-dashboard-grid-attention">
    <x-information.chart.figure id="decision-queue" title="طلبات تنتظر قراراً" icon="clock" :subtitle="$decisionQueue['overdue'] > 0 ? $decisionQueue['overdue'].' منها تجاوزت العتبة' : null">
        <x-slot:action><a class="info-chart-action" href="{{ $decisionQueue['href'] }}">عرض الكل ({{ $decisionQueue['total'] }})</a></x-slot:action>
        @if (count($decisionQueue['items']) === 0)
            <p class="info-chart-empty">لا توجد طلبات مفتوحة ضمن الفترة.</p>
        @else
            <div class="info-chart-table-scroll"><table class="info-chart-table info-attention-table"><thead><tr><th>الطلب</th><th>الميناء</th><th>الحالة</th><th>العمر</th></tr></thead><tbody>
                @foreach ($decisionQueue['items'] as $item)
                    <tr @class(['is-alert' => $item['overdue']])><th><a href="{{ $item['href'] }}">{{ $item['reference'] }}</a></th><td>{{ $item['port'] }}</td><td><x-information.status-chip :status="$item['status']" /></td><td>{{ $item['age_days'] }} يوم</td></tr>
                @endforeach
            </tbody></table></div>
        @endif
    </x-information.chart.figure>

    <x-information.chart.figure id="expiry" title="رخص تحتاج متابعة" icon="shield" :subtitle="$expiringLicenses['expired'] > 0 ? $expiringLicenses['expired'].' رخصة منتهية بالفعل' : null">
        <x-slot:action><a class="info-chart-action" href="{{ $expiringLicenses['href'] }}">عرض الكل ({{ $expiringLicenses['total'] }})</a></x-slot:action>
        @if (count($expiringLicenses['items']) === 0)
            <p class="info-chart-empty">لا توجد رخص قريبة من الانتهاء.</p>
        @else
            <div class="info-chart-table-scroll"><table class="info-chart-table info-attention-table"><thead><tr><th>الطلب</th><th>الميناء</th><th>تاريخ الانتهاء</th><th>المتبقي</th></tr></thead><tbody>
                @foreach ($expiringLicenses['items'] as $item)
                    <tr @class(['is-alert' => $item['expired']])><th><a href="{{ $item['href'] }}">{{ $item['reference'] }}</a></th><td>{{ $item['port'] }}</td><td>{{ $item['expiry'] }}</td><td>{{ $item['expired'] ? 'منتهية' : $item['days_left'].' يوم' }}</td></tr>
                @endforeach
            </tbody></table></div>
        @endif
    </x-information.chart.figure>

    <section class="info-chart-figure info-dashboard-activity is-wide" aria-labelledby="activity-title">
        <div class="info-chart-head"><div><h2 id="activity-title"><svg class="info-chart-head-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h3.5L9 5l4 14 2.5-7H21"></path></svg><span>النشاط الأخير</span></h2></div></div>
        @if (count($recentActivity) === 0)
            <p class="info-chart-empty">لا يوجد نشاط ضمن الفترة المحددة.</p>
        @else
            <ol class="info-dashboard-activity-list">
                @foreach ($recentActivity as $activity)
                    <li><span class="info-dashboard-activity-marker" aria-hidden="true"></span><div><a href="{{ $activity['href'] }}">{{ $activity['reference'] }}</a><p>{{ $activity['actor'] }} · {{ \App\Models\InformationSubmission::STATUS_LABELS[$activity['from_status']] ?? 'بداية الطلب' }} ← {{ \App\Models\InformationSubmission::STATUS_LABELS[$activity['to_status']] ?? $activity['to_status'] }}</p></div><time datetime="{{ $activity['created_at']->toIso8601String() }}">{{ $activity['created_at']->diffForHumans() }}</time></li>
                @endforeach
            </ol>
        @endif
    </section>
</section>
