{{--
    تصفية تقارير التحليل (filter في hispa): من تاريخ وإلى تاريخ، والقارب
    اختياريًا (`showBoat`)، ثم "تحديث" و"طباعة" بالتصفية نفسها. `printable`
    = false حين تكون الطباعة في رأس الصفحة، و`bare` داخل بطاقة لها عنوانها.
--}}
<form method="GET" action="{{ route('panel.owner.reports.show', $key) }}" class="filter-bar" @if ($bare ?? false) style="border:0;padding:0;background:none" @else style="margin-bottom:1.25rem" @endif>
    <label class="field"><span>من تاريخ</span><input class="input" type="date" name="from" value="{{ $from }}" required dir="ltr"></label>
    <label class="field"><span>إلى تاريخ</span><input class="input" type="date" name="to" value="{{ $to }}" required dir="ltr"></label>
    @if ($showBoat ?? true)
        <label class="field" style="min-width:12rem"><span>القارب</span>
            <select class="select" name="boat_id">
                <option value="">كل القوارب</option>
                @foreach ($boats as $b)<option value="{{ $b->id }}" @selected($boatId === $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </label>
    @endif
    <div style="display:flex;gap:.5rem;margin-inline-start:auto">
        <button class="btn btn-primary">@include('partials.icon', ['name' => 'search']) تحديث</button>
        @if ($printable ?? true)
            <a href="{{ route('panel.owner.reports.show', ['report' => $key, 'mode' => 'print'] + $query) }}" target="_blank" class="btn btn-outline">@include('partials.icon', ['name' => 'printer']) طباعة</a>
        @endif
    </div>
</form>
