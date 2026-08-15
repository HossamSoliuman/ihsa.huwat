@extends('layouts.app')

@section('title', 'المصيد المعتمد')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'badge-check'])</div>
            <div>
                <h1>المصيد المعتمد</h1>
                <p>اعتماد كميات المصيد بعد الإحصاء الميداني — المصدر الرسمي لمؤشرات الإنتاج</p>
            </div>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'بانتظار الاعتماد', 'value' => $stats['awaiting'], 'icon' => 'clock', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'رحلات معتمدة', 'value' => $stats['approved'], 'icon' => 'badge-check', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'المصيد المعتمد', 'value' => number_format($stats['approved_kg']), 'unit' => 'كجم', 'icon' => 'fish', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'القيمة التقديرية', 'value' => number_format($stats['value']), 'unit' => 'ريال', 'icon' => 'scale', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'متوسط الرحلة', 'value' => number_format($stats['avg']), 'unit' => 'كجم', 'icon' => 'trending-up', 'tone' => 'info'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>الميناء</span>
            <select class="select" name="port" onchange="this.form.submit()">
                <option value="">كل الموانئ</option>
                @foreach ($ports as $port)<option value="{{ $port->id }}" @selected(request('port') == $port->id)>{{ $port->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">الكل</option>
                @foreach (['بانتظار الاعتماد', 'معتمدة'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
        </label>
        <a href="{{ route('approved-catch') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الرحلة</th><th>القارب</th><th>الميناء</th><th>الوزن الفعلي</th><th>الأنواع</th><th>المعتمد</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($trips as $trip)
                    <tr>
                        <td style="font-family:monospace;font-size:.72rem;font-weight:700">{{ $trip->trip_number }}</td>
                        <td>{{ $trip->boat?->name ?? '—' }}</td>
                        <td>{{ $trip->departurePort?->name ?? '—' }}</td>
                        <td>{{ $trip->actual_weight_kg ? number_format($trip->actual_weight_kg) : '—' }}</td>
                        <td style="font-size:.72rem;color:hsl(var(--muted-foreground))">{{ $trip->catchRecords->map(fn ($r) => $r->species?->name_ar)->filter()->implode('، ') ?: '—' }}</td>
                        <td style="font-weight:700">{{ $trip->approved_kg ? number_format($trip->approved_kg) : '—' }}</td>
                        <td><span class="badge {{ $trip->status === 'معتمدة' ? 'badge-ok' : 'badge-warn' }}">{{ $trip->status }}</span></td>
                        <td>
                            @if ($trip->status !== 'معتمدة')
                                <form method="POST" action="{{ route('approved-catch.approve', $trip) }}" onsubmit="return confirm('اعتماد مصيد الرحلة {{ $trip->trip_number }}؟')">
                                    @csrf
                                    <button class="btn btn-primary" style="padding:.35rem .75rem;font-size:.72rem">@include('partials.icon', ['name' => 'badge-check']) اعتماد</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد رحلات في مرحلة الاعتماد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection