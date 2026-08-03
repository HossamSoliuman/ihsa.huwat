@props([
    'index',
    'member' => [],
    'nationalities' => [],
    'roles' => [],
])

@php
    $prefix = "crew_members.{$index}";
    $idPrefix = "crew_{$index}";
@endphp

<article class="info-repeat-card" data-info-crew-row>
    <header class="info-repeat-card-header">
        <span class="info-repeat-index" aria-hidden="true" data-info-row-number>{{ is_numeric($index) ? ((int) $index + 1) : '' }}</span>
        <div>
            <strong>بيانات البحار <span data-info-row-title>{{ is_numeric($index) ? ((int) $index + 1) : '' }}</span></strong>
            <small>الهوية والتواصل والدور على متن القارب</small>
        </div>
        <button type="button" class="info-repeat-remove" data-info-remove-crew aria-label="حذف سجل البحار">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m-9 0 1 14h10l1-14"></path></svg>
            <span>حذف</span>
        </button>
    </header>

    <div class="info-field-grid info-field-grid-four info-repeat-fields">
        @php($field = "{$prefix}.full_name")
        <div class="info-field info-field-span-two">
            <label for="{{ $idPrefix }}_full_name">الاسم الرباعي <b aria-hidden="true">*</b></label>
            <input id="{{ $idPrefix }}_full_name" name="crew_members[{{ $index }}][full_name]" value="{{ data_get($member, 'full_name') }}" required minlength="3" maxlength="150" placeholder="الاسم كما يظهر في الهوية" @error($field) aria-invalid="true" aria-describedby="{{ $idPrefix }}_full_name_error" @enderror>
            @error($field)<small id="{{ $idPrefix }}_full_name_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        @php($field = "{$prefix}.identity_number")
        <div class="info-field">
            <label for="{{ $idPrefix }}_identity_number">رقم الهوية / الإقامة <b aria-hidden="true">*</b></label>
            <input id="{{ $idPrefix }}_identity_number" name="crew_members[{{ $index }}][identity_number]" value="{{ data_get($member, 'identity_number') }}" required inputmode="numeric" pattern="[12١٢][0-9٠-٩۰-۹]{9}" maxlength="10" dir="ltr" placeholder="10 أرقام" @error($field) aria-invalid="true" aria-describedby="{{ $idPrefix }}_identity_number_error" @enderror>
            @error($field)<small id="{{ $idPrefix }}_identity_number_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        @php($field = "{$prefix}.passport_number")
        <div class="info-field">
            <label for="{{ $idPrefix }}_passport_number">رقم الجواز</label>
            <input id="{{ $idPrefix }}_passport_number" name="crew_members[{{ $index }}][passport_number]" value="{{ data_get($member, 'passport_number') }}" maxlength="30" dir="ltr" placeholder="اختياري" @error($field) aria-invalid="true" aria-describedby="{{ $idPrefix }}_passport_number_error" @enderror>
            @error($field)<small id="{{ $idPrefix }}_passport_number_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        @php($field = "{$prefix}.phone")
        <div class="info-field">
            <label for="{{ $idPrefix }}_phone">رقم الجوال <b aria-hidden="true">*</b></label>
            <input id="{{ $idPrefix }}_phone" name="crew_members[{{ $index }}][phone]" value="{{ data_get($member, 'phone') }}" required inputmode="tel" maxlength="15" dir="ltr" placeholder="05xxxxxxxx" @error($field) aria-invalid="true" aria-describedby="{{ $idPrefix }}_phone_error" @enderror>
            @error($field)<small id="{{ $idPrefix }}_phone_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        @php($field = "{$prefix}.birth_date")
        <div class="info-field">
            <label for="{{ $idPrefix }}_birth_date">تاريخ الميلاد <b aria-hidden="true">*</b></label>
            <input type="date" id="{{ $idPrefix }}_birth_date" name="crew_members[{{ $index }}][birth_date]" value="{{ data_get($member, 'birth_date') }}" required max="{{ today()->format('Y-m-d') }}" dir="ltr" @error($field) aria-invalid="true" aria-describedby="{{ $idPrefix }}_birth_date_error" @enderror>
            @error($field)<small id="{{ $idPrefix }}_birth_date_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        @php($field = "{$prefix}.nationality")
        <div class="info-field">
            <label for="{{ $idPrefix }}_nationality">الجنسية <b aria-hidden="true">*</b></label>
            <select id="{{ $idPrefix }}_nationality" name="crew_members[{{ $index }}][nationality]" required @error($field) aria-invalid="true" aria-describedby="{{ $idPrefix }}_nationality_error" @enderror>
                <option value="">اختر الجنسية</option>
                @foreach($nationalities as $value => $label)
                    <option value="{{ $value }}" @selected(data_get($member, 'nationality') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error($field)<small id="{{ $idPrefix }}_nationality_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        @php($field = "{$prefix}.role")
        <div class="info-field">
            <label for="{{ $idPrefix }}_role">الدور على القارب <b aria-hidden="true">*</b></label>
            <select id="{{ $idPrefix }}_role" name="crew_members[{{ $index }}][role]" required @error($field) aria-invalid="true" aria-describedby="{{ $idPrefix }}_role_error" @enderror>
                <option value="">اختر الدور</option>
                @foreach($roles as $value => $label)
                    <option value="{{ $value }}" @selected(data_get($member, 'role') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error($field)<small id="{{ $idPrefix }}_role_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        @php($field = "{$prefix}.experience_years")
        <div class="info-field">
            <label for="{{ $idPrefix }}_experience_years">سنوات الخبرة <b aria-hidden="true">*</b></label>
            <input type="number" id="{{ $idPrefix }}_experience_years" name="crew_members[{{ $index }}][experience_years]" value="{{ data_get($member, 'experience_years') }}" required min="0" max="60" inputmode="numeric" placeholder="0" @error($field) aria-invalid="true" aria-describedby="{{ $idPrefix }}_experience_years_error" @enderror>
            @error($field)<small id="{{ $idPrefix }}_experience_years_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>
    </div>
</article>
