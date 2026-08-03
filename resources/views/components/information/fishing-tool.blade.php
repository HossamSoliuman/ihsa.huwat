@props([
    'index',
    'tool' => [],
    'types' => [],
    'materials' => [],
    'conditions' => [],
])

@php
    $prefix = "fishing_tools.{$index}";
    $idPrefix = "tool_{$index}";
@endphp

<article class="info-repeat-card info-tool-card" data-info-tool-row>
    <header class="info-repeat-card-header">
        <span class="info-repeat-index" aria-hidden="true" data-info-row-number>{{ is_numeric($index) ? ((int) $index + 1) : '' }}</span>
        <div>
            <strong>أداة الصيد <span data-info-row-title>{{ is_numeric($index) ? ((int) $index + 1) : '' }}</span></strong>
            <small>النوع والكمية والمقاس والحالة التشغيلية</small>
        </div>
        <button type="button" class="info-repeat-remove" data-info-remove-tool aria-label="حذف أداة الصيد">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m-9 0 1 14h10l1-14"></path></svg>
            <span>حذف</span>
        </button>
    </header>

    <div class="info-field-grid info-field-grid-six info-repeat-fields">
        @php($field = "{$prefix}.type")
        <div class="info-field info-field-span-two">
            <label for="{{ $idPrefix }}_type">نوع الأداة <b aria-hidden="true">*</b></label>
            <select id="{{ $idPrefix }}_type" name="fishing_tools[{{ $index }}][type]" required @error($field) aria-invalid="true" aria-describedby="{{ $idPrefix }}_type_error" @enderror>
                <option value="">اختر الأداة</option>
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" @selected(data_get($tool, 'type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error($field)<small id="{{ $idPrefix }}_type_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        @php($field = "{$prefix}.quantity")
        <div class="info-field">
            <label for="{{ $idPrefix }}_quantity">العدد <b aria-hidden="true">*</b></label>
            <input type="number" id="{{ $idPrefix }}_quantity" name="fishing_tools[{{ $index }}][quantity]" value="{{ data_get($tool, 'quantity') }}" required min="1" max="10000" inputmode="numeric" placeholder="1" @error($field) aria-invalid="true" aria-describedby="{{ $idPrefix }}_quantity_error" @enderror>
            @error($field)<small id="{{ $idPrefix }}_quantity_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        @php($field = "{$prefix}.size")
        <div class="info-field">
            <label for="{{ $idPrefix }}_size">المقاس</label>
            <input id="{{ $idPrefix }}_size" name="fishing_tools[{{ $index }}][size]" value="{{ data_get($tool, 'size') }}" maxlength="50" placeholder="مثال: 3 إنش" @error($field) aria-invalid="true" aria-describedby="{{ $idPrefix }}_size_error" @enderror>
            @error($field)<small id="{{ $idPrefix }}_size_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        @php($field = "{$prefix}.material")
        <div class="info-field">
            <label for="{{ $idPrefix }}_material">المادة <b aria-hidden="true">*</b></label>
            <select id="{{ $idPrefix }}_material" name="fishing_tools[{{ $index }}][material]" required @error($field) aria-invalid="true" aria-describedby="{{ $idPrefix }}_material_error" @enderror>
                <option value="">اختر المادة</option>
                @foreach($materials as $value => $label)
                    <option value="{{ $value }}" @selected(data_get($tool, 'material') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error($field)<small id="{{ $idPrefix }}_material_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        @php($field = "{$prefix}.condition")
        <div class="info-field">
            <label for="{{ $idPrefix }}_condition">الحالة <b aria-hidden="true">*</b></label>
            <select id="{{ $idPrefix }}_condition" name="fishing_tools[{{ $index }}][condition]" required @error($field) aria-invalid="true" aria-describedby="{{ $idPrefix }}_condition_error" @enderror>
                <option value="">اختر الحالة</option>
                @foreach($conditions as $value => $label)
                    <option value="{{ $value }}" @selected(data_get($tool, 'condition') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error($field)<small id="{{ $idPrefix }}_condition_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        @php($field = "{$prefix}.is_primary")
        <div class="info-field info-tool-primary">
            <span class="info-field-label">الأداة الأساسية</span>
            <input type="hidden" name="fishing_tools[{{ $index }}][is_primary]" value="0">
            <label for="{{ $idPrefix }}_is_primary" class="info-switch">
                <input type="checkbox" id="{{ $idPrefix }}_is_primary" name="fishing_tools[{{ $index }}][is_primary]" value="1" data-info-primary-tool @checked((bool) data_get($tool, 'is_primary'))>
                <span aria-hidden="true"></span>
                <small>تعيين كأساسية</small>
            </label>
            @error($field)<small id="{{ $idPrefix }}_is_primary_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>
    </div>
</article>
