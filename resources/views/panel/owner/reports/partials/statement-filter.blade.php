{{--
    تصفية كشوف الحساب (statement-filter في hispa): الطرف (مطلوب)، ومن تاريخ
    وإلى تاريخ اختياريان، ثم "تحديث"، و"طباعة" بعد اختيار الطرف.
    `groups` = [['label' => ?string, 'items' => [...{id, name}]]].
--}}
<form method="GET" action="{{ route('panel.owner.reports.show', $key) }}" class="filter-bar" style="margin-bottom:1.25rem">
    <label class="field" style="min-width:14rem"><span>{{ $entityLabel }}</span>
        <select class="select" name="{{ $entityParam }}" required>
            <option value="">{{ $placeholder }}</option>
            @foreach ($groups as $group)
                @if ($group['label'])
                    <optgroup label="{{ $group['label'] }}">
                        @foreach ($group['items'] as $item)<option value="{{ $item->id }}" @selected($selectedId === $item->id)>{{ $item->name }}</option>@endforeach
                    </optgroup>
                @else
                    @foreach ($group['items'] as $item)<option value="{{ $item->id }}" @selected($selectedId === $item->id)>{{ $item->name }}</option>@endforeach
                @endif
            @endforeach
        </select>
    </label>
    <label class="field"><span>من تاريخ</span><input class="input" type="date" name="from" value="{{ $from }}" dir="ltr"></label>
    <label class="field"><span>إلى تاريخ</span><input class="input" type="date" name="to" value="{{ $to }}" dir="ltr"></label>
    <div style="display:flex;gap:.5rem;margin-inline-start:auto">
        <button class="btn btn-primary">@include('partials.icon', ['name' => 'search']) تحديث</button>
        @if ($selectedId)
            <a href="{{ route('panel.owner.reports.show', ['report' => $key, 'mode' => 'print'] + $query) }}" target="_blank" class="btn btn-outline">@include('partials.icon', ['name' => 'printer']) طباعة</a>
        @endif
    </div>
</form>
