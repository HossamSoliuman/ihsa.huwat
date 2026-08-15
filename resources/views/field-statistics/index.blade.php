@extends('layouts.app')

@section('title', 'الإحصاء الميداني')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'clipboard-check'])</div>
            <div>
                <h1>الإحصاء الميداني</h1>
                <p>طابور الرحلات العائدة: تسجيل الوزن الفعلي ومقارنته بإدخال الكابتن</p>
            </div>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="stat-grid cols-6" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'عادت للميناء', 'value' => $stats['returned'], 'icon' => 'anchor', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'بانتظار الإحصاء', 'value' => $stats['pending'], 'icon' => 'clock', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'تحت الإحصاء', 'value' => $stats['under'], 'icon' => 'clipboard-check', 'tone' => 'warning'])
        @include('partials.stat-card', ['label' => 'بانتظار الاعتماد', 'value' => $stats['awaiting'], 'icon' => 'badge-check', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'المعلن من الكباتن', 'value' => number_format($stats['declared']), 'unit' => 'كجم', 'icon' => 'scale', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'الموزون فعليًا', 'value' => number_format($stats['measured']), 'unit' => 'كجم', 'icon' => 'scale', 'tone' => 'success'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>حالة الرحلة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">كل الطابور</option>
                @foreach (['عادت للميناء', 'بانتظار الإحصاء', 'تحت الإحصاء', 'بانتظار الاعتماد'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
        </label>
        <a href="{{ route('field-statistics') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الرحلة</th><th>القارب</th><th>الميناء</th><th>العودة</th><th>إدخال الكابتن</th><th>الوزن الفعلي</th><th>الفرق</th><th>موظف الإحصاء</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($trips as $trip)
                    <tr>
                        <td style="font-family:monospace;font-size:.72rem;font-weight:700">{{ $trip->trip_number }}</td>
                        <td>{{ $trip->boat?->name ?? '—' }}</td>
                        <td>{{ $trip->departurePort?->name ?? '—' }}</td>
                        <td style="white-space:nowrap">{{ $trip->return_time?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>{{ $trip->captain_input_kg ? number_format($trip->captain_input_kg) : '—' }}</td>
                        <td>{{ $trip->actual_weight_kg ? number_format($trip->actual_weight_kg) : '—' }}</td>
                        <td style="color:{{ abs((float) $trip->diff_kg) > 0 ? '#e11d48' : 'inherit' }}">{{ $trip->diff_kg !== null ? number_format($trip->diff_kg, 1) : '—' }}</td>
                        <td>{{ $trip->statistics_officer ?? '—' }}</td>
                        <td><span class="badge {{ $trip->status === 'بانتظار الاعتماد' ? 'badge-info' : 'badge-warn' }}">{{ $trip->status }}</span></td>
                        <td>
                            <button type="button" class="icon-action" title="تسجيل الإحصاء" onclick='openStatsForm({!! json_encode($trip->only(['id', 'trip_number', 'captain_input_kg', 'actual_weight_kg', 'statistics_officer', 'notes']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>
                                @include('partials.icon', ['name' => 'scale'])
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">طابور الإحصاء فارغ حالياً</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="drawer-overlay" id="statsDrawer-overlay" onclick="toggleDrawer('statsDrawer', false)"></div>
    <div class="drawer" id="statsDrawer">
        <div class="drawer-head">
            <div>
                <h3>تسجيل الإحصاء الميداني</h3>
                <p style="font-size:.72rem;color:hsl(var(--muted-foreground))" id="statsSub"></p>
            </div>
            <button type="button" class="icon-action" onclick="toggleDrawer('statsDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="statsForm" class="drawer-body">
            @csrf
            <label class="field"><span>الوزن الفعلي (كجم) *</span><input class="input" type="number" step="0.01" min="0" name="actual_weight_kg" id="st-weight" required></label>
            <label class="field"><span>موظف الإحصاء</span><input class="input" name="statistics_officer" id="st-officer"></label>
            <label class="field"><span>ملاحظات</span><textarea class="input" rows="3" name="notes" id="st-notes"></textarea></label>
            <div style="display:flex;justify-content:flex-end;gap:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('statsDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ الإحصاء</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const recordBase = @json(url('field-statistics'));

    function openStatsForm(trip) {
        document.getElementById('statsForm').action = recordBase + '/' + trip.id + '/record';
        document.getElementById('statsSub').textContent = 'الرحلة ' + trip.trip_number + ' — إدخال الكابتن: ' + (trip.captain_input_kg ?? '—') + ' كجم';
        document.getElementById('st-weight').value = trip.actual_weight_kg ?? '';
        document.getElementById('st-officer').value = trip.statistics_officer ?? '';
        document.getElementById('st-notes').value = trip.notes ?? '';
        toggleDrawer('statsDrawer', true);
    }
</script>
@endpush