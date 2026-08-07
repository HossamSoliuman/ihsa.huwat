@props([
    'market',
    'type',
    'unit' => null,
])

@php
    /**
     * A محل and a دكة ask the same questions, so one form serves both and `unit_type` only
     * travels on the create request — a unit's kind is settled when it is added.
     */
    $isEdit = $unit !== null;
    $formKey = $isEdit ? 'unit-'.$unit->id : 'unit-new-'.$type;
    $idPrefix = $formKey;
    $values = $unit ?? [];
    $active = old('form_key') === $formKey;

    $labelValue = $active ? old('label', data_get($values, 'label')) : data_get($values, 'label');
    $labelError = $active ? $errors->first('label') : null;
    $isActive = $active && $errors->any()
        ? (bool) old('is_active')
        : (bool) (data_get($values, 'is_active') ?? true);

    $typeLabel = \App\Models\FishMarketUnit::TYPE_LABELS[$type];
@endphp

<form class="info-admin-form" method="post"
      action="{{ $isEdit
          ? route('information.admin.markets.units.update', [$market, $unit])
          : route('information.admin.markets.units.store', $market) }}">
    @csrf
    @if ($isEdit)
        @method('PATCH')
    @else
        <input type="hidden" name="unit_type" value="{{ $type }}">
    @endif

    <input type="hidden" name="form_key" value="{{ $formKey }}">

    <div class="info-field-grid info-field-grid-three">
        <div class="info-field">
            <label for="{{ $idPrefix }}_label">التعريف</label>
            <input id="{{ $idPrefix }}_label" name="label" value="{{ $labelValue }}" maxlength="100"
                   placeholder="مثال: {{ $type === \App\Models\FishMarketUnit::TYPE_SHOP ? 'محل 1' : 'دكة 1' }}"
                   @if ($labelError) aria-invalid="true" aria-describedby="{{ $idPrefix }}_label_error" @endif>
            @if ($labelError)<small id="{{ $idPrefix }}_label_error" class="info-field-error">{{ $labelError }}</small>@endif
        </div>

        <x-information.commercial-entity
            :id-prefix="$idPrefix"
            :values="$values"
            :active="$active"
            :with-municipality-licence="true"
        />
    </div>

    <div class="info-admin-form-actions">
        <label class="info-lookup-check">
            <input type="checkbox" name="is_active" value="1" @checked($isActive)>
            <span>نشط</span>
        </label>

        <button class="info-button info-button-primary" type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"></path></svg>
            <span>{{ $isEdit ? 'حفظ التعديلات' : 'إضافة '.$typeLabel }}</span>
        </button>
    </div>
</form>
