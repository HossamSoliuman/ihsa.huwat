@props([
    'idPrefix',
    'regions' => [],
    'governorates' => [],
    'selected' => null,
    'selectedRegion' => null,
    'required' => true,
    'showRequiredMark' => false,
    'regionLabel' => 'المنطقة',
    'governorateLabel' => 'المحافظة',
    'regionName' => null,
    'governorateName' => 'governorate_id',
    'governorateFilters' => null,
])

@php
    /**
     * The governorate list runs to a hundred-odd entries, so the region select in front of
     * it narrows the choices. It carries no `name`, so it never submits — المنطقة is read
     * back through the governorate.
     */
    $selectedGovernorate = old($governorateName, $selected);
    $restoredRegion = old($regionName, $selectedRegion);
@endphp

<div class="info-field">
    <label for="{{ $idPrefix }}-region">{{ $regionLabel }}</label>
    <select id="{{ $idPrefix }}-region" @if ($regionName) name="{{ $regionName }}" @endif data-lookup-region-filter="{{ $idPrefix }}-governorate">
        <option value="">كل المناطق</option>
        @foreach ($regions as $region)
            <option value="{{ $region->id }}" @selected((string) $restoredRegion === (string) $region->id)>{{ $region->name }}</option>
        @endforeach
    </select>
</div>

<div class="info-field">
    <label for="{{ $idPrefix }}-governorate">{{ $governorateLabel }} @if ($showRequiredMark)<b aria-hidden="true">*</b>@endif</label>
    <select id="{{ $idPrefix }}-governorate" name="{{ $governorateName }}" @required($required)
            @if ($governorateFilters) data-lookup-governorate-filter="{{ $governorateFilters }}" @endif
            @error('governorate_id') aria-invalid="true" aria-describedby="{{ $idPrefix }}_governorate_error" @enderror>
        <option value="">اختر {{ $governorateLabel }}</option>
        @foreach ($governorates as $governorate)
            <option value="{{ $governorate->id }}" data-region="{{ $governorate->region_id }}"
                    @selected((string) $selectedGovernorate === (string) $governorate->id)>{{ $governorate->name }}</option>
        @endforeach
    </select>
    @error('governorate_id')<small id="{{ $idPrefix }}_governorate_error" class="info-field-error">{{ $message }}</small>@enderror
</div>
