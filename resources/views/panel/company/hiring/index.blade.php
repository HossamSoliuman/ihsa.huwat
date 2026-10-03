@extends('layouts.app')

@section('title', 'جولات التوظيف')

@php
    use App\Models\HiringRound;
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'calendar'])</div>
            <div>
                <h1>جولات التوظيف</h1>
            </div>
        </div>
        <div class="actions">
            @if ($ports->isNotEmpty())
                <button type="button" class="btn btn-primary" onclick="openRoundForm()">@include('partials.icon', ['name' => 'plus']) جولة جديدة</button>
            @endif
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    @if ($ports->isEmpty())
        <div class="flash-error">لم يُسند إلى شركتك ميناء بعد — يسنده المدير العام، ثم تفتح فيه جولة.</div>
    @endif

    @include('panel.company.partials.apply-link')

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">الكل</option>
                @foreach (HiringRound::STATUS_LABELS as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach
            </select>
        </label>
        <a href="{{ route('panel.company.hiring') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الجولة</th><th>الميناء</th><th>المدة</th><th>المعتمدون / المقاعد</th><th>بانتظارك</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($rounds as $round)
                    <tr>
                        <td style="font-weight:600">{{ $round->title }}@if ($round->notes)<div class="card-sub">{{ $round->notes }}</div>@endif</td>
                        <td>{{ $round->port?->name }}</td>
                        <td class="num" style="font-size:.74rem"><bdi dir="ltr">{{ $round->opens_at->format('Y-m-d') }}</bdi> ← <bdi dir="ltr">{{ $round->closes_at->format('Y-m-d') }}</bdi></td>
                        <td class="num">{{ $round->approved_count }} / {{ $round->seats }}</td>
                        <td class="num">
                            @if ($round->pending_count > 0)
                                <a href="{{ route('panel.company.applications', ['round' => $round->id]) }}">{{ $round->pending_count }}</a>
                            @else
                                0
                            @endif
                        </td>
                        <td><span class="badge {{ $round->state_tone }}">{{ $round->state_label }}</span></td>
                        <td>
                            <div style="display:flex;gap:.25rem;justify-content:flex-end">
                                <button type="button" class="icon-action" title="تعديل"
                                    onclick='openRoundForm({!! json_encode(['id' => $round->id, 'port_id' => $round->port_id, 'title' => $round->title, 'seats' => $round->seats, 'opens_at' => $round->opens_at->toDateString(), 'closes_at' => $round->closes_at->toDateString(), 'status' => $round->status, 'notes' => $round->notes], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!})'>
                                    @include('partials.icon', ['name' => 'pencil'])
                                </button>
                                @if ($round->status === HiringRound::OPEN)
                                    <form method="POST" action="{{ route('panel.company.hiring.close', $round->id) }}" onsubmit="return confirm('إغلاق الجولة؟ يتوقف التقديم فيها.')">
                                        @csrf
                                        <button type="submit" class="icon-action" title="إغلاق">@include('partials.icon', ['name' => 'lock'])</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('panel.company.hiring.open', $round->id) }}">
                                        @csrf
                                        <button type="submit" class="icon-action" title="فتح">@include('partials.icon', ['name' => 'lock-open'])</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا جولات توظيف بعد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pagination', ['paginator' => $rounds])

    <div class="drawer-overlay" id="roundDrawer-overlay" onclick="toggleDrawer('roundDrawer', false)"></div>
    <div class="drawer" id="roundDrawer">
        <div class="drawer-head">
            <h3 id="roundFormTitle">جولة جديدة</h3>
            <button type="button" class="icon-action" onclick="toggleDrawer('roundDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
        </div>
        <form method="POST" id="roundForm" action="{{ route('panel.company.hiring.store') }}" class="drawer-body" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" id="roundMethod" value="POST">
            <input type="hidden" name="id" id="r-id" value="">
            <div class="form-grid cols-2">
                <label class="field" style="grid-column:1/-1"><span>عنوان الجولة *</span><input class="input" name="title" id="r-title" required maxlength="255" placeholder="توظيف عدّادين — موسم الشتاء"></label>
                <label class="field"><span>الميناء *</span>
                    <select class="select" name="port_id" id="r-port" required>
                        <option value="">— اختر —</option>
                        @foreach ($ports as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>عدد المقاعد *</span><input class="input num" type="number" name="seats" id="r-seats" min="1" max="500" required></label>
                <label class="field"><span>تفتح في *</span><input class="input" type="date" name="opens_at" id="r-opens" required></label>
                <label class="field"><span>تُغلق في *</span><input class="input" type="date" name="closes_at" id="r-closes" required></label>
                <label class="field" style="grid-column:1/-1"><span>الحالة *</span>
                    <select class="select" name="status" id="r-status" required>
                        @foreach (HiringRound::STATUS_LABELS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </label>
                <label class="field" style="grid-column:1/-1"><span>ملاحظات للمتقدّمين</span><textarea class="input" name="notes" id="r-notes" rows="3" maxlength="2000" placeholder="الشروط أو ساعات العمل — تظهر في صفحة التقديم"></textarea></label>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
                <button type="button" class="btn btn-outline" onclick="toggleDrawer('roundDrawer', false)">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const roundStoreUrl = @json(route('panel.company.hiring.store'));

    function openRoundForm(round = null) {
        const form = document.getElementById('roundForm');
        document.getElementById('roundFormTitle').textContent = round?.id ? 'تعديل الجولة' : 'جولة جديدة';
        document.getElementById('roundMethod').value = round?.id ? 'PUT' : 'POST';
        form.action = round?.id ? roundStoreUrl + '/' + round.id : roundStoreUrl;
        document.getElementById('r-id').value = round?.id ?? '';
        document.getElementById('r-title').value = round?.title ?? '';
        document.getElementById('r-port').value = round?.port_id ?? '';
        document.getElementById('r-seats').value = round?.seats ?? 3;
        document.getElementById('r-opens').value = round?.opens_at ?? @json(today()->toDateString());
        document.getElementById('r-closes').value = round?.closes_at ?? @json(today()->addMonth()->toDateString());
        document.getElementById('r-status').value = round?.status ?? 'open';
        document.getElementById('r-notes').value = round?.notes ?? '';
        toggleDrawer('roundDrawer', true);
    }

    @if ($errors->any() && old('title') !== null)
        openRoundForm({!! json_encode(['id' => old('id') ?: null] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
    @endif
</script>
@endpush
