@if (! empty($kpis))
    <div class="stat-grid cols-{{ min(count($kpis), 6) }}" style="margin-bottom:1.25rem">
        @foreach ($kpis as $kpi)
            @include('partials.stat-card', [
                'label' => $kpi['label'],
                'value' => \App\Services\Owner\OwnerReports::format($kpi['value'], $kpi['format']),
                'unit' => match ($kpi['format']) { 'money' => 'ر.س', 'kg' => 'كجم', default => null },
                'icon' => $kpi['icon'] ?? match ($kpi['format']) { 'money' => 'coins', 'kg' => 'scale', 'pct' => 'gauge', default => 'hash' },
                'tone' => ($kpi['strong'] ?? false) ? ((float) $kpi['value'] < 0 ? 'danger' : 'success') : 'primary',
            ])
        @endforeach
    </div>
@endif
