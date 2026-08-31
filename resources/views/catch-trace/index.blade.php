@extends('layouts.app')

@section('title', 'تتبع المصيد')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'search'])</div>
            <div>
                <h1>تتبع المصيد</h1>
            </div>
        </div>
    </div>

    <form method="GET" class="filter-bar">
        <label class="field" style="min-width:18rem"><span>رقم الرحلة</span><input class="input" name="search" value="{{ $search }}" placeholder="مثال: TRP-1024"></label>
        <button class="btn btn-primary">@include('partials.icon', ['name' => 'search']) تتبع</button>
    </form>

    @if ($search && ! $trip)
        <div class="card" style="padding:2rem;text-align:center;font-size:.875rem;color:hsl(var(--muted-foreground))">لا توجد رحلة مطابقة لرقم «{{ $search }}»</div>
    @endif

    @if ($trip)
        <div class="card">
            <p class="card-title" style="margin-bottom:.75rem">سلسلة التتبع</p>
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                @foreach ([$trip->boat?->port?->governorate?->region?->name, $trip->boat?->port?->governorate?->name, $trip->departurePort?->name, $trip->boat?->name, $trip->trip_number] as $node)
                    <div class="hier-chip">{{ $node ?? '—' }}</div>
                    @if (! $loop->last)<span style="color:hsl(var(--muted-foreground))">←</span>@endif
                @endforeach
            </div>
        </div>

        @include('partials.section-head', ['icon' => 'scale', 'title' => 'أوزان الرحلة'])

        <div class="stat-grid cols-5">
            @include('partials.stat-card', ['label' => 'حالة الرحلة', 'value' => $trip->status, 'icon' => 'activity', 'tone' => 'info'])
            @include('partials.stat-card', ['label' => 'إدخال الكابتن', 'value' => number_format($trip->captain_input_kg), 'unit' => 'كجم', 'icon' => 'scale', 'tone' => 'primary'])
            @include('partials.stat-card', ['label' => 'الوزن الفعلي', 'value' => number_format($trip->actual_weight_kg), 'unit' => 'كجم', 'icon' => 'scale', 'tone' => 'primary'])
            @include('partials.stat-card', ['label' => 'الفرق', 'value' => number_format($trip->diff_kg, 1), 'unit' => 'كجم', 'icon' => 'alert-triangle', 'tone' => abs((float) $trip->diff_kg) > 0 ? 'warning' : 'success'])
            @include('partials.stat-card', ['label' => 'المصيد المعتمد', 'value' => number_format($trip->approved_kg), 'unit' => 'كجم', 'icon' => 'badge-check', 'tone' => 'success'])
        </div>

        @include('partials.section-head', ['icon' => 'fish', 'title' => 'الأنواع المسجّلة'])

        <div class="table-card">
            <table class="data-table">
                <thead><tr><th>النوع</th><th>الكمية (كجم)</th><th>متوسط الوزن</th><th>السعر/كجم</th><th>القيمة</th><th>تاريخ التسجيل</th></tr></thead>
                <tbody>
                    @forelse ($trip->catchRecords as $record)
                        <tr>
                            <td style="font-weight:600">{{ $record->species?->name_ar ?? '—' }}</td>
                            <td>{{ number_format($record->quantity_kg) }}</td>
                            <td>{{ $record->avg_weight_kg ?? '—' }}</td>
                            <td>{{ $record->price_per_kg ?? '—' }}</td>
                            <td>{{ $record->total_value ? number_format($record->total_value) : '—' }}</td>
                            <td>{{ $record->recorded_at?->toDateString() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="padding:1.5rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد سجلات مصيد لهذه الرحلة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        @include('partials.section-head', ['icon' => 'history', 'title' => 'رحلات معتمدة حديثة'])
        <div class="cards-grid cols-4">
            @foreach ($recent as $item)
                <a href="{{ route('stats.catch-trace', ['search' => $item->trip_number]) }}" class="entity-card" style="padding:1rem">
                    <span style="font-family:monospace;font-size:.72rem;font-weight:700;color:hsl(var(--primary))">{{ $item->trip_number }}</span>
                    <p style="margin-top:.35rem;font-size:.875rem;font-weight:600">{{ $item->boat?->name ?? '—' }}</p>
                    <p style="font-size:.72rem;color:hsl(var(--muted-foreground))">المعتمد: {{ number_format($item->approved_kg) }} كجم</p>
                </a>
            @endforeach
        </div>
    @endif
@endsection