<x-information.section-head title="صلاحية الرخص" subtitle="توزيع زمني يوضح حجم المخاطر قبل تحوّلها إلى قوائم متابعة" icon="chart" />

<section class="info-dashboard-grid">
    <x-information.chart.figure id="licence-expiry" title="رخص المراكب" icon="shield" :legend="$licenceExpiry['segments']" class="is-wide">
        <x-information.chart.stacked-bar :bars="[$licenceExpiry]" />
        <x-slot:table>
            <table class="info-chart-table"><thead><tr><th>الحالة</th><th>الرخص</th></tr></thead><tbody>
                @foreach ($licenceExpiry['segments'] as $segment)
                    <tr><th>{{ $segment['label'] }}</th><td>{{ $segment['value'] }}</td></tr>
                @endforeach
            </tbody></table>
        </x-slot:table>
    </x-information.chart.figure>
</section>
