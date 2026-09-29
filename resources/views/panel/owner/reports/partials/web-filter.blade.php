{{--
    تصفية التقرير: `range` = months (من شهر إلى شهر) أو dates (من تاريخ إلى تاريخ)،
    و`boat` = true لقارب أو "general" لقارب أو "عام". الطباعة وExcel تأخذان التصفية نفسها.
--}}
<form method="GET" action="{{ route('panel.owner.reports.show', $key) }}" class="filter-bar" style="margin-bottom:1.25rem;display:flex;flex-wrap:wrap;align-items:flex-end;gap:.65rem">
    @if (($range ?? null) === 'months')
        <label class="field"><span>من شهر</span><input class="input" type="month" name="from" value="{{ $from->format('Y-m') }}" max="{{ now()->format('Y-m') }}" dir="ltr"></label>
        <label class="field"><span>إلى شهر</span><input class="input" type="month" name="to" value="{{ $to->format('Y-m') }}" max="{{ now()->format('Y-m') }}" dir="ltr"></label>
    @elseif (($range ?? null) === 'dates')
        <label class="field"><span>من</span><input class="input" type="date" name="from" value="{{ $from->format('Y-m-d') }}" dir="ltr"></label>
        <label class="field"><span>إلى</span><input class="input" type="date" name="to" value="{{ $to->format('Y-m-d') }}" dir="ltr"></label>
    @endif
    @if ($boat ?? false)
        <label class="field"><span>القارب</span>
            <select class="select" name="boat_id">
                <option value="">{{ $boat === 'general' ? 'الكل' : 'كل الأسطول' }}</option>
                @if ($boat === 'general')<option value="general" @selected(($query['boat_id'] ?? '') === 'general')>عام (بلا قارب)</option>@endif
                @foreach ($boats as $b)<option value="{{ $b->id }}" @selected((string) ($query['boat_id'] ?? '') === (string) $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </label>
    @endif
    <button class="btn btn-primary">@include('partials.icon', ['name' => 'search']) عرض</button>
    @if (! empty($presets))
        <nav class="seg" aria-label="فترات جاهزة" style="margin-inline-start:auto">
            @foreach ($presets as $label => $params)
                <a href="{{ route('panel.owner.reports.show', ['report' => $key] + $params + array_intersect_key($query, ['boat_id' => 1])) }}" style="padding-block:.5rem">{{ $label }}</a>
            @endforeach
        </nav>
    @endif
</form>
