@php
    $key = $field['key'];
    $type = $field['type'];
    $old = old($key);
    $wide = in_array($type, ['textarea'], true);
@endphp

@if ($type === 'boolean')
    <div class="field field-check">
        <input type="hidden" name="{{ $key }}" value="0">
        <input type="checkbox" id="field-{{ $key }}" name="{{ $key }}" value="1" data-field="{{ $key }}" @checked($old)>
        <label for="field-{{ $key }}">{{ $field['label'] }}</label>
    </div>
@else
    <div class="field @if ($wide) field-wide @endif">
        <label for="field-{{ $key }}">
            {{ $field['label'] }}
            @if ($field['required'])<span class="req">*</span>@endif
        </label>

        @if ($type === 'select')
            <select class="select" id="field-{{ $key }}" name="{{ $key }}" data-field="{{ $key }}">
                <option value="">— اختر —</option>
                @foreach ($field['options'] as $value => $label)
                    <option value="{{ $value }}" @selected($old !== null && (string) $old === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        @elseif ($type === 'textarea')
            <textarea class="input" id="field-{{ $key }}" name="{{ $key }}" data-field="{{ $key }}">{{ $old }}</textarea>
        @elseif ($type === 'number')
            <input class="input" type="number" step="any" id="field-{{ $key }}" name="{{ $key }}" data-field="{{ $key }}" value="{{ $old }}">
        @elseif ($type === 'date')
            <input class="input" type="date" id="field-{{ $key }}" name="{{ $key }}" data-field="{{ $key }}" value="{{ $old }}">
        @else
            <input class="input" type="text" id="field-{{ $key }}" name="{{ $key }}" data-field="{{ $key }}" value="{{ $old }}">
        @endif
    </div>
@endif