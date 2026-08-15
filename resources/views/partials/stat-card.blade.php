<div class="stat-card">
    <div style="min-width:0">
        <p class="label">{{ $label }}</p>
        <p class="value">{{ $value }}@if (!empty($unit))<span class="unit">{{ $unit }}</span>@endif</p>
    </div>
    <div class="kpi-icon {{ $tone ?? 'primary' }}">@include('partials.icon', ['name' => $icon])</div>
</div>