@props([
    'idPrefix',
    'prefix' => null,
    'values' => [],
    'active' => true,
    'withContact' => false,
    'withPhone' => null,
    'withEmail' => null,
    'withMunicipalityLicence' => false,
    'required' => true,
    'entityNameField' => 'entity_name',
    'entityNameLabel' => 'الشركة / المؤسسة',
    'phoneLabel' => 'التليفون',
])

@php
    /**
     * معلومات المستثمر, a محل, a دكة and a دلال منشأة all ask the same commercial-entity
     * questions and differ on two axes only: whether the block carries contact details and
     * whether it carries a رخصة البلدية. Two booleans beat four near-identical fieldsets.
     *
     * `prefix` is what puts the block on its own columns — `investor` yields
     * `investor_commercial_registration_no` — and the investor's name column is
     * `investor_name` rather than `investor_entity_name`, hence `entityNameField`.
     *
     * A market page hosts several of these blocks at once and `old()` is shared by all of
     * them, so `active` marks the one form that was actually submitted. Only that block
     * repopulates and only it prints the validation errors.
     */
    $showPhone = $withPhone ?? $withContact;
    $showEmail = $withEmail ?? $withContact;

    $nameFor = fn (string $field): string => $prefix ? $prefix.'_'.$field : $field;
    $idFor = fn (string $field): string => $idPrefix.'_'.$field;
    $valueOf = fn (string $field): mixed => $active
        ? old($nameFor($field), data_get($values, $nameFor($field)))
        : data_get($values, $nameFor($field));
    $errorFor = fn (string $field): ?string => $active ? $errors->first($nameFor($field)) : null;

    $fields = array_filter([
        [$entityNameField, $entityNameLabel, 'span', $required, ['minlength' => 3, 'maxlength' => 190, 'placeholder' => 'الاسم كما في السجل التجاري']],
        ['commercial_registration_no', 'رقم السجل التجاري', 'code', $required, ['maxlength' => 60, 'placeholder' => '1010xxxxxx']],
        $withMunicipalityLicence
            ? ['municipality_licence_no', 'رخصة البلدية', 'code', false, ['maxlength' => 60, 'placeholder' => 'رقم الرخصة']]
            : null,
        $showPhone
            ? ['phone', $phoneLabel, 'code', false, ['maxlength' => 15, 'inputmode' => 'tel', 'placeholder' => '05xxxxxxxx']]
            : null,
        $showEmail
            ? ['email', 'البريد الإلكتروني', 'code', false, ['type' => 'email', 'maxlength' => 190, 'placeholder' => 'name@example.com']]
            : null,
        ['tax_number', 'الرقم الضريبي', 'code', false, ['maxlength' => 60, 'placeholder' => '3xxxxxxxxxxxx03']],
        ['national_address', 'العنوان الوطني', 'span', false, ['maxlength' => 190, 'placeholder' => 'المبنى والشارع والحي والمدينة']],
    ]);
@endphp

@foreach ($fields as [$field, $label, $shape, $isRequired, $attributes])
    @php
        $name = $nameFor($field);
        $id = $idFor($field);
        $error = $errorFor($field);
    @endphp

    <div @class(['info-field', 'info-field-span-two' => $shape === 'span'])>
        <label for="{{ $id }}">{{ $label }} @if ($isRequired)<b aria-hidden="true">*</b>@endif</label>
        <input id="{{ $id }}" name="{{ $name }}" value="{{ $valueOf($field) }}" @required($isRequired)
               @if ($shape === 'code') dir="ltr" @endif
               @foreach ($attributes as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach
               @if ($error) aria-invalid="true" aria-describedby="{{ $id }}_error" @endif>
        @if ($error)<small id="{{ $id }}_error" class="info-field-error">{{ $error }}</small>@endif
    </div>
@endforeach
