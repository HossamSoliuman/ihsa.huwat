@props([
    'market' => null,
    'regions' => [],
    'governorates' => [],
    'active' => true,
])

@php
    /**
     * One form for both جديد and تعديل: the market and its investor are a single POST, and
     * the units and their عمالة are added from the market page afterwards.
     */
    $isEdit = $market !== null;
    $formKey = $isEdit ? 'market-'.$market->id : 'market-new';
    $idPrefix = $formKey;
    $values = $market ?? [];

    $nameValue = $active ? old('name', data_get($values, 'name')) : data_get($values, 'name');
    $nameError = $active ? $errors->first('name') : null;
    /** An unchecked box submits nothing, so only a rejected submission may read `old()`. */
    $isActive = $active && $errors->any()
        ? (bool) old('is_active')
        : (bool) (data_get($values, 'is_active') ?? true);
@endphp

<form class="info-admin-form" method="post"
      action="{{ $isEdit ? route('information.admin.markets.update', $market) : route('information.admin.markets.store') }}">
    @csrf
    @if ($isEdit)
        @method('PATCH')
    @endif

    {{-- Marks which form on the page was submitted, so only it repopulates and shows errors. --}}
    <input type="hidden" name="form_key" value="{{ $formKey }}">

    <fieldset class="info-admin-fieldset">
        <legend>موقع السوق</legend>

        <div class="info-field-grid info-field-grid-three">
            <x-information.region-governorate-select
                :id-prefix="$idPrefix"
                :regions="$regions"
                :governorates="$governorates"
                :selected="data_get($values, 'governorate_id')"
                :show-required-mark="true"
            />

            <div class="info-field">
                <label for="{{ $idPrefix }}_name">اسم السوق <b aria-hidden="true">*</b></label>
                <input id="{{ $idPrefix }}_name" name="name" value="{{ $nameValue }}" required minlength="2" maxlength="150"
                       placeholder="مثال: سوق السمك المركزي بجدة"
                       @if ($nameError) aria-invalid="true" aria-describedby="{{ $idPrefix }}_name_error" @endif>
                @if ($nameError)<small id="{{ $idPrefix }}_name_error" class="info-field-error">{{ $nameError }}</small>@endif
            </div>
        </div>
    </fieldset>

    <fieldset class="info-admin-fieldset">
        <legend>معلومات المستثمر</legend>

        <div class="info-field-grid info-field-grid-three">
            <x-information.commercial-entity
                prefix="investor"
                :id-prefix="$idPrefix.'_investor'"
                :values="$values"
                :active="$active"
                entity-name-field="name"
                :with-contact="true"
            />
        </div>
    </fieldset>

    <div class="info-admin-form-actions">
        <label class="info-lookup-check">
            <input type="checkbox" name="is_active" value="1" @checked($isActive)>
            <span>سوق نشط</span>
        </label>

        <button class="info-button info-button-primary" type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"></path></svg>
            <span>{{ $isEdit ? 'حفظ التعديلات' : 'إنشاء السوق' }}</span>
        </button>
    </div>
</form>
