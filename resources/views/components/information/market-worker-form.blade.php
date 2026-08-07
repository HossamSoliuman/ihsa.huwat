@props([
    'market',
    'unit',
    'worker' => null,
    'nationalities' => [],
    'jobTitles' => [],
])

@php
    $isEdit = $worker !== null;
    $formKey = $isEdit ? 'worker-'.$worker->id : 'worker-new-'.$unit->id;
    $idPrefix = $formKey;
    $values = $worker ?? [];
    $active = old('form_key') === $formKey;

    $valueOf = fn (string $field): mixed => $active
        ? old($field, data_get($values, $field))
        : data_get($values, $field);
    $errorFor = fn (string $field): ?string => $active ? $errors->first($field) : null;
@endphp

<form class="info-admin-form" method="post"
      action="{{ $isEdit
          ? route('information.admin.markets.units.workers.update', [$market, $unit, $worker])
          : route('information.admin.markets.units.workers.store', [$market, $unit]) }}">
    @csrf
    @if ($isEdit)
        @method('PATCH')
    @endif

    <input type="hidden" name="form_key" value="{{ $formKey }}">

    <div class="info-field-grid info-field-grid-three">
        @php($error = $errorFor('full_name'))
        <div class="info-field info-field-span-two">
            <label for="{{ $idPrefix }}_full_name">الاسم <b aria-hidden="true">*</b></label>
            <input id="{{ $idPrefix }}_full_name" name="full_name" value="{{ $valueOf('full_name') }}" required
                   minlength="3" maxlength="150" placeholder="الاسم كما يظهر في الهوية"
                   @if ($error) aria-invalid="true" aria-describedby="{{ $idPrefix }}_full_name_error" @endif>
            @if ($error)<small id="{{ $idPrefix }}_full_name_error" class="info-field-error">{{ $error }}</small>@endif
        </div>

        @php($error = $errorFor('national_id'))
        <div class="info-field">
            <label for="{{ $idPrefix }}_national_id">رقم الهوية / الإقامة <b aria-hidden="true">*</b></label>
            <input id="{{ $idPrefix }}_national_id" name="national_id" value="{{ $valueOf('national_id') }}" required
                   inputmode="numeric" pattern="[12١٢][0-9٠-٩۰-۹]{9}" maxlength="10" dir="ltr" placeholder="10 أرقام"
                   @if ($error) aria-invalid="true" aria-describedby="{{ $idPrefix }}_national_id_error" @endif>
            @if ($error)<small id="{{ $idPrefix }}_national_id_error" class="info-field-error">{{ $error }}</small>@endif
        </div>

        @php($error = $errorFor('phone'))
        <div class="info-field">
            <label for="{{ $idPrefix }}_phone">رقم التلفون</label>
            <input id="{{ $idPrefix }}_phone" name="phone" value="{{ $valueOf('phone') }}"
                   inputmode="tel" maxlength="15" dir="ltr" placeholder="05xxxxxxxx"
                   @if ($error) aria-invalid="true" aria-describedby="{{ $idPrefix }}_phone_error" @endif>
            @if ($error)<small id="{{ $idPrefix }}_phone_error" class="info-field-error">{{ $error }}</small>@endif
        </div>

        @php($error = $errorFor('email'))
        <div class="info-field">
            <label for="{{ $idPrefix }}_email">البريد الإلكتروني</label>
            <input id="{{ $idPrefix }}_email" type="email" name="email" value="{{ $valueOf('email') }}"
                   maxlength="190" dir="ltr" placeholder="name@example.com"
                   @if ($error) aria-invalid="true" aria-describedby="{{ $idPrefix }}_email_error" @endif>
            @if ($error)<small id="{{ $idPrefix }}_email_error" class="info-field-error">{{ $error }}</small>@endif
        </div>

        @php($error = $errorFor('nationality'))
        <div class="info-field">
            <label for="{{ $idPrefix }}_nationality">الجنسية <b aria-hidden="true">*</b></label>
            <select id="{{ $idPrefix }}_nationality" name="nationality" required
                    @if ($error) aria-invalid="true" aria-describedby="{{ $idPrefix }}_nationality_error" @endif>
                <option value="">اختر الجنسية</option>
                @foreach ($nationalities as $code => $label)
                    <option value="{{ $code }}" @selected((string) $valueOf('nationality') === (string) $code)>{{ $label }}</option>
                @endforeach
            </select>
            @if ($error)<small id="{{ $idPrefix }}_nationality_error" class="info-field-error">{{ $error }}</small>@endif
        </div>

        @php($error = $errorFor('job_title'))
        <div class="info-field">
            <label for="{{ $idPrefix }}_job_title">الوظيفة <b aria-hidden="true">*</b></label>
            <select id="{{ $idPrefix }}_job_title" name="job_title" required
                    @if ($error) aria-invalid="true" aria-describedby="{{ $idPrefix }}_job_title_error" @endif>
                <option value="">اختر الوظيفة</option>
                @foreach ($jobTitles as $code => $label)
                    <option value="{{ $code }}" @selected((string) $valueOf('job_title') === (string) $code)>{{ $label }}</option>
                @endforeach
            </select>
            @if ($error)<small id="{{ $idPrefix }}_job_title_error" class="info-field-error">{{ $error }}</small>@endif
        </div>
    </div>

    <div class="info-admin-form-actions">
        <button class="info-button info-button-primary" type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
            <span>{{ $isEdit ? 'حفظ التعديلات' : 'إضافة عامل' }}</span>
        </button>
    </div>
</form>
